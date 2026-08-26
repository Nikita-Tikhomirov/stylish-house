<?php

namespace App\Services\CatalogImport\Publication;

use App\Data\CatalogImport\VerifiedDatabaseBackup;
use App\Models\CatalogImportRun;
use JsonException;

final class CatalogImportDatabaseBackupVerifier
{
    private readonly CatalogImportBackupArtifactVerifier $artifacts;

    public function __construct(?CatalogImportBackupArtifactVerifier $artifacts = null)
    {
        $this->artifacts = $artifacts ?? new CatalogImportBackupArtifactVerifier;
    }

    public function verifyCreated(
        VerifiedDatabaseBackup $backup,
        string $expectedRunId,
        string $expectedProvider,
    ): string {
        $archive = $this->artifacts->fingerprintGzip(
            $backup->archivePath,
            'verified database backup archive',
        );
        $manifestArtifact = $this->artifacts->readFile(
            $backup->manifestPath,
            'verified database backup manifest',
        );
        if ($archive['gzip_size'] !== $backup->gzipSize
            || ! hash_equals($backup->gzipSha256, $archive['gzip_sha256'])) {
            throw new CatalogImportPublicationException('Verified database backup artifact changed before recording.');
        }
        $manifestBytes = $manifestArtifact['bytes'];
        try {
            $manifest = json_decode((string) $manifestBytes, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new CatalogImportPublicationException('Verified database backup manifest cannot be read.');
        }
        if (! is_array($manifest) || $manifest !== $backup->manifest
            || ! $this->manifestMatches(
                $manifest,
                $expectedRunId,
                $expectedProvider,
                $backup->rawSha256,
                $backup->rawSize,
                $backup->gzipSha256,
                $backup->gzipSize,
            )) {
            throw new CatalogImportPublicationException('Verified database backup manifest changed before recording.');
        }
        if ($archive['raw_sha256'] !== $backup->rawSha256
            || $archive['raw_size'] !== $backup->rawSize) {
            throw new CatalogImportPublicationException('Verified database backup archive failed independent verification.');
        }

        return hash('sha256', (string) $manifestBytes);
    }

    public function assertRecordedRollback(CatalogImportRun $run): void
    {
        $this->assertRecorded(
            run: $run,
            expectedRunId: $run->external_run_id,
            createdAt: $run->rollback_backup_created_at,
            archivePath: $run->rollback_backup_path,
            gzipSha256: $run->rollback_backup_sha256,
            manifestPath: $run->rollback_backup_manifest_path,
            manifestSha256: $run->rollback_backup_manifest_sha256,
            rawSha256: $run->rollback_backup_raw_sha256,
            rawSize: $run->rollback_backup_raw_size,
            gzipSize: $run->rollback_backup_gzip_size,
            label: 'Recorded rollback database backup',
        );
    }

    public function assertRecordedPublication(CatalogImportRun $run): void
    {
        $this->assertRecorded(
            run: $run,
            expectedRunId: $run->external_run_id,
            createdAt: $run->backup_created_at,
            archivePath: $run->backup_path,
            gzipSha256: $run->backup_sha256,
            manifestPath: $run->backup_manifest_path,
            manifestSha256: $run->backup_manifest_sha256,
            rawSha256: $run->backup_raw_sha256,
            rawSize: $run->backup_raw_size,
            gzipSize: $run->backup_gzip_size,
            label: 'Published run recorded verified backup',
        );
    }

    private function assertRecorded(
        CatalogImportRun $run,
        string $expectedRunId,
        mixed $createdAt,
        mixed $archivePath,
        mixed $gzipSha256,
        mixed $manifestPath,
        mixed $manifestSha256,
        mixed $rawSha256,
        mixed $rawSize,
        mixed $gzipSize,
        string $label,
    ): void {
        if ($createdAt === null || ! is_string($archivePath) || ! is_string($manifestPath)
            || ! is_string($gzipSha256) || ! preg_match('/^[a-f0-9]{64}$/D', $gzipSha256)
            || ! is_string($manifestSha256) || ! preg_match('/^[a-f0-9]{64}$/D', $manifestSha256)
            || ! is_string($rawSha256) || ! preg_match('/^[a-f0-9]{64}$/D', $rawSha256)
            || ! is_int($rawSize) || $rawSize < 1 || ! is_int($gzipSize) || $gzipSize < 1) {
            throw new CatalogImportPublicationException($label.' is missing or incomplete.');
        }
        $archive = $this->artifacts->fingerprintGzip($archivePath, strtolower($label).' archive');
        $manifestArtifact = $this->artifacts->readFile($manifestPath, strtolower($label).' manifest');
        if ($archive['gzip_size'] !== $gzipSize
            || ! hash_equals($gzipSha256, $archive['gzip_sha256'])
            || ! hash_equals($manifestSha256, $manifestArtifact['sha256'])) {
            throw new CatalogImportPublicationException($label.' is missing or changed.');
        }
        $manifestBytes = $manifestArtifact['bytes'];
        try {
            $manifest = json_decode((string) $manifestBytes, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new CatalogImportPublicationException($label.' manifest is invalid.');
        }
        if (! is_array($manifest) || ! $this->manifestMatches(
            $manifest,
            $expectedRunId,
            $run->provider,
            $rawSha256,
            $rawSize,
            $gzipSha256,
            $gzipSize,
        )) {
            throw new CatalogImportPublicationException($label.' manifest changed.');
        }
        if ($archive['raw_sha256'] !== $rawSha256 || $archive['raw_size'] !== $rawSize) {
            throw new CatalogImportPublicationException($label.' archive changed.');
        }
    }

    /** @param array<string, mixed> $manifest */
    private function manifestMatches(
        array $manifest,
        string $runId,
        string $provider,
        string $rawSha256,
        int $rawSize,
        string $gzipSha256,
        int $gzipSize,
    ): bool {
        return ($manifest['schema'] ?? null) === 'catalog-import-database-backup'
            && ($manifest['version'] ?? null) === 1
            && ($manifest['run']['id'] ?? null) === $runId
            && ($manifest['run']['provider'] ?? null) === $provider
            && ($manifest['raw']['sha256'] ?? null) === $rawSha256
            && ($manifest['raw']['size'] ?? null) === $rawSize
            && ($manifest['gzip']['sha256'] ?? null) === $gzipSha256
            && ($manifest['gzip']['size'] ?? null) === $gzipSize;
    }
}
