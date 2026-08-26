<?php

namespace App\Services\CatalogImport\Publication;

use App\Data\CatalogImport\DatabaseBackupRequest;
use App\Models\CatalogImportRun;
use App\Services\CatalogImport\DatabaseBackup\DatabaseBackupService;

final class CatalogImportBackupRecorder
{
    public function __construct(
        private readonly DatabaseBackupService $backup,
        private readonly CatalogImportMutationLock $lock,
        private readonly CatalogImportDatabaseBackupVerifier $verifier = new CatalogImportDatabaseBackupVerifier,
    ) {}

    public function create(CatalogImportRun $run): CatalogImportRun
    {
        return $this->lock->synchronized(function () use ($run): CatalogImportRun {
            $current = $run->fresh();
            if ($current === null) {
                throw new CatalogImportPublicationException('Catalog import run no longer exists.');
            }
            if (! in_array($current->status, [
                CatalogImportRun::STATUS_STAGED,
                CatalogImportRun::STATUS_REVIEWING,
            ], true) || $current->publication_journal !== null) {
                throw new CatalogImportPublicationException(
                    'A standalone publication backup is allowed only for an unpublished run without a media journal.'
                );
            }

            $verified = $this->backup->create(new DatabaseBackupRequest(
                runId: $current->external_run_id,
                provider: $current->provider,
                connectionName: (string) config('database.default'),
                connection: (array) config('database.connections.'.config('database.default'), []),
            ));
            $manifestSha256 = $this->verifier->verifyCreated(
                $verified,
                $current->external_run_id,
                $current->provider,
            );

            $updated = CatalogImportRun::query()
                ->whereKey($current->id)
                ->whereIn('status', [CatalogImportRun::STATUS_STAGED, CatalogImportRun::STATUS_REVIEWING])
                ->whereNull('publication_journal')
                ->update([
                    'backup_created_at' => $verified->verifiedAt,
                    'backup_path' => $verified->archivePath,
                    'backup_sha256' => $verified->gzipSha256,
                    'backup_manifest_path' => $verified->manifestPath,
                    'backup_manifest_sha256' => $manifestSha256,
                    'backup_raw_sha256' => $verified->rawSha256,
                    'backup_raw_size' => $verified->rawSize,
                    'backup_gzip_size' => $verified->gzipSize,
                    'updated_at' => now(),
                ]);
            if ($updated !== 1) {
                throw new CatalogImportPublicationException(
                    'Verified database backup could not be bound to the unchanged catalog import run.'
                );
            }

            $recorded = $current->fresh() ?? throw new CatalogImportPublicationException(
                'Catalog import run no longer exists after backup recording.'
            );
            $this->verifier->assertRecordedPublication($recorded);

            return $recorded;
        });
    }
}
