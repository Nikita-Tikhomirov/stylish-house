<?php

namespace App\Console\Commands;

use App\Services\CatalogImport\Publication\CatalogImportBackupRecorder;
use App\Services\CatalogImport\Publication\CatalogImportPublicationException;
use Throwable;

final class BackupCatalogDatabase extends CatalogImportCommand
{
    protected $signature = 'catalog:backup
        {--run= : External run id or database id}';

    protected $description = 'Create, independently verify, and record a private database backup for an import run';

    public function handle(CatalogImportBackupRecorder $recorder): int
    {
        try {
            $identity = $this->option('run');
            if (! is_string($identity) || trim($identity) === '') {
                throw new CatalogImportPublicationException('The --run option is required.');
            }
            $run = $this->resolveRun($identity);
            $recorded = $recorder->create($run);
            $this->info('Backed up run='.$recorded->external_run_id);
            $this->line('archive='.$recorded->backup_path);
            $this->line('manifest='.$recorded->backup_manifest_path);
            $this->line('gzip_sha256='.$recorded->backup_sha256);
            $this->line('raw_sha256='.$recorded->backup_raw_sha256);

            return self::SUCCESS;
        } catch (Throwable $error) {
            return $this->reportFailure($error);
        }
    }
}
