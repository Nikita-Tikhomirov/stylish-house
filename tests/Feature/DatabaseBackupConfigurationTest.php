<?php

namespace Tests\Feature;

use App\Data\CatalogImport\DatabaseBackupInvocation;
use App\Data\CatalogImport\DatabaseBackupRequest;
use App\Services\CatalogImport\DatabaseBackup\BackupPermissionHardener;
use App\Services\CatalogImport\DatabaseBackup\DatabaseBackupService;
use App\Services\CatalogImport\DatabaseBackup\DatabaseDumpProcessFactory;
use App\Services\CatalogImport\DatabaseBackup\DatabaseDumpRunner;
use App\Services\CatalogImport\DatabaseBackup\NativeBackupPermissionHardener;
use App\Services\CatalogImport\DatabaseBackup\SymfonyDatabaseDumpProcessFactory;
use App\Services\CatalogImport\DatabaseBackup\SymfonyDatabaseDumpRunner;
use Illuminate\Filesystem\Filesystem;
use Tests\TestCase;

class DatabaseBackupConfigurationTest extends TestCase
{
    public function test_default_backup_configuration_is_private_and_fail_closed(): void
    {
        $this->assertSame(storage_path('app/catalog-backups'), config('catalog-import-backup.destination'));
        $this->assertSame([
            public_path(),
            storage_path('app/public'),
        ], config('catalog-import-backup.public_roots'));
        $this->assertNull(config('catalog-import-backup.dump_binary'));
        $this->assertSame(900, config('catalog-import-backup.timeout_seconds'));
        $this->assertInstanceOf(SymfonyDatabaseDumpRunner::class, $this->app->make(DatabaseDumpRunner::class));
        $this->assertInstanceOf(
            NativeBackupPermissionHardener::class,
            $this->app->make(BackupPermissionHardener::class),
        );
        $this->assertInstanceOf(
            SymfonyDatabaseDumpProcessFactory::class,
            $this->app->make(DatabaseDumpProcessFactory::class),
        );
    }

    public function test_container_resolves_the_backup_service_from_private_configuration(): void
    {
        $sandbox = sys_get_temp_dir().DIRECTORY_SEPARATOR.'backup-container-test-'.bin2hex(random_bytes(8));
        $destination = $sandbox.DIRECTORY_SEPARATOR.'private'.DIRECTORY_SEPARATOR.'catalog-backups';
        $publicRoot = $sandbox.DIRECTORY_SEPARATOR.'public';
        mkdir($publicRoot, 0700, true);

        try {
            config()->set('catalog-import-backup.destination', $destination);
            config()->set('catalog-import-backup.public_roots', [$publicRoot]);
            config()->set('catalog-import-backup.dump_binary', null);
            config()->set('catalog-import-backup.timeout_seconds', 77);
            $runner = new ConfiguredBackupRunner;
            $this->app->instance(DatabaseDumpRunner::class, $runner);
            $this->app->instance(BackupPermissionHardener::class, new ConfiguredBackupPermissionHardener);

            $service = $this->app->make(DatabaseBackupService::class);
            $backup = $service->create(new DatabaseBackupRequest(
                runId: 'container-run-001',
                provider: 'rimskie.com',
                connectionName: 'catalog-primary',
                connection: [
                    'driver' => 'mysql',
                    'host' => '127.0.0.1',
                    'port' => 3306,
                    'database' => 'stylish_house',
                    'username' => 'backup_operator',
                    'password' => 'container-secret',
                    'charset' => 'utf8mb4',
                ],
            ));

            $this->assertInstanceOf(DatabaseBackupService::class, $service);
            $this->assertSame(77, $runner->invocation?->timeoutSeconds);
            $this->assertStringStartsWith(realpath($destination).DIRECTORY_SEPARATOR, $backup->archivePath);
            $this->assertStringStartsWith(realpath($destination).DIRECTORY_SEPARATOR, $backup->manifestPath);
        } finally {
            if (str_starts_with(basename($sandbox), 'backup-container-test-')) {
                (new Filesystem)->deleteDirectory($sandbox);
            }
        }
    }
}

final class ConfiguredBackupPermissionHardener implements BackupPermissionHardener
{
    public function isSupported(): bool
    {
        return true;
    }

    public function secureDirectory(string $path): bool
    {
        return is_dir($path);
    }

    public function secureFile(
        string $path,
        int $device,
        int $inode,
        int $expectedLinkCount,
    ): bool {
        $stat = @lstat($path);

        return is_array($stat)
            && $stat['dev'] === $device
            && $stat['ino'] === $inode
            && $stat['nlink'] === $expectedLinkCount;
    }

    public function enforcesPosixPermissions(): bool
    {
        return false;
    }
}

final class ConfiguredBackupRunner implements DatabaseDumpRunner
{
    public ?DatabaseBackupInvocation $invocation = null;

    public function run(DatabaseBackupInvocation $invocation): void
    {
        $this->invocation = $invocation;
        file_put_contents($invocation->outputPath, "CREATE TABLE `configured` (`id` bigint);\n");
    }
}
