<?php

namespace App\Providers;

use App\Services\CatalogImport\DatabaseBackup\BackupPermissionHardener;
use App\Services\CatalogImport\DatabaseBackup\DatabaseBackupService;
use App\Services\CatalogImport\DatabaseBackup\DatabaseDumpProcessFactory;
use App\Services\CatalogImport\DatabaseBackup\DatabaseDumpRunner;
use App\Services\CatalogImport\DatabaseBackup\GzipBackupArchive;
use App\Services\CatalogImport\DatabaseBackup\NativeBackupPermissionHardener;
use App\Services\CatalogImport\DatabaseBackup\SymfonyDatabaseDumpProcessFactory;
use App\Services\CatalogImport\DatabaseBackup\SymfonyDatabaseDumpRunner;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class CatalogImportBackupServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(DatabaseDumpProcessFactory::class, SymfonyDatabaseDumpProcessFactory::class);
        $this->app->bind(DatabaseDumpRunner::class, SymfonyDatabaseDumpRunner::class);
        $this->app->bind(BackupPermissionHardener::class, NativeBackupPermissionHardener::class);

        $this->app->bind(DatabaseBackupService::class, function (Application $app): DatabaseBackupService {
            $config = $app->make('config')->get('catalog-import-backup', []);

            return new DatabaseBackupService(
                runner: $app->make(DatabaseDumpRunner::class),
                archive: new GzipBackupArchive,
                destination: is_string($config['destination'] ?? null) ? $config['destination'] : '',
                publicRoots: is_array($config['public_roots'] ?? null) ? $config['public_roots'] : [],
                binary: is_string($config['dump_binary'] ?? null) ? $config['dump_binary'] : null,
                timeoutSeconds: (int) ($config['timeout_seconds'] ?? 900),
                permissionHardener: $app->make(BackupPermissionHardener::class),
            );
        });
    }
}
