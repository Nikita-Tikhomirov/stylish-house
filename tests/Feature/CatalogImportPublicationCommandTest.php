<?php

namespace Tests\Feature;

use App\Data\CatalogImport\DatabaseBackupInvocation;
use App\Data\CatalogImport\DatabaseBackupRequest;
use App\Data\CatalogImport\VerifiedDatabaseBackup;
use App\Models\CatalogImportRun;
use App\Services\CatalogImport\DatabaseBackup\DatabaseBackupException;
use App\Services\CatalogImport\DatabaseBackup\DatabaseBackupService;
use App\Services\CatalogImport\DatabaseBackup\DatabaseDumpRunner;
use App\Services\CatalogImport\DatabaseBackup\GzipBackupArchive;
use App\Services\CatalogImport\Publication\CatalogImportSitemapGenerator;
use DateTimeImmutable;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Tests\Support\CatalogImportPublicationTestCase;

final class CatalogImportPublicationCommandTest extends CatalogImportPublicationTestCase
{
    private string $backupDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->backupDirectory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'command-backup-'.bin2hex(random_bytes(8));
        mkdir($this->backupDirectory, 0700, true);
        config()->set('catalog-import-backup.destination', $this->backupDirectory);
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->backupDirectory);

        parent::tearDown();
    }

    public function test_preflight_command_reports_exact_reviewed_counts(): void
    {
        $this->seedCatalogRoots();
        $this->seedReviewedRun();

        $this->artisan('catalog-import:preflight', ['run' => 'full-run-001'])
            ->expectsOutputToContain(
                'Preflight passed run=full-run-001 sources=46 products=1 memberships=46 public_attributes=1 warnings=0'
            )
            ->assertSuccessful();
    }

    public function test_standalone_backup_command_verifies_then_records_artifact_metadata(): void
    {
        $run = $this->seedReviewedRun();
        $backup = new CommandTestBackupService(
            $this->backupDirectory,
            beforeCreate: function () use ($run): void {
                $this->assertNull($run->fresh()->backup_created_at);
                $this->assertNull($run->fresh()->backup_path);
            },
        );
        $this->app->instance(DatabaseBackupService::class, $backup);

        $this->artisan('catalog:backup', ['--run' => 'full-run-001'])
            ->expectsOutputToContain('Backed up run=full-run-001')
            ->expectsOutputToContain('gzip_sha256=')
            ->expectsOutputToContain('raw_sha256=')
            ->assertSuccessful();

        $recorded = $run->fresh();
        $this->assertSame(1, $backup->calls);
        $this->assertNotNull($recorded->backup_created_at);
        $this->assertFileExists($recorded->backup_path);
        $this->assertFileExists($recorded->backup_manifest_path);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/D', $recorded->backup_manifest_sha256);
        $this->assertSame(CatalogImportRun::STATUS_REVIEWING, $recorded->status);
        $this->assertNull($recorded->warnings_acknowledged_at);
        $this->assertSame(0, DB::table('products')->count());
    }

    public function test_standalone_backup_command_failure_is_safe_and_records_nothing(): void
    {
        $run = $this->seedReviewedRun();
        $backup = new CommandTestBackupService($this->backupDirectory, fail: true);
        $this->app->instance(DatabaseBackupService::class, $backup);

        $this->artisan('catalog:backup', ['--run' => 'full-run-001'])
            ->expectsOutputToContain('Catalog import operation failed safely; correlation=')
            ->doesntExpectOutputToContain('SECRET_DUMP_FAILURE')
            ->assertFailed();

        $this->assertSame(1, $backup->calls);
        $this->assertNull($run->fresh()->backup_created_at);
        $this->assertNull($run->fresh()->backup_path);
        $this->assertSame(0, DB::table('products')->count());
    }

    public function test_publish_command_feature_gate_refuses_before_warning_acknowledgement_or_backup(): void
    {
        $this->seedCatalogRoots();
        $run = $this->seedReviewedRun();
        $run->items()->firstOrFail()->update(['warnings' => ['controlled warning']]);
        $backup = new CommandTestBackupService($this->backupDirectory);
        $this->app->instance(DatabaseBackupService::class, $backup);
        config()->set('catalog-import-publication.enabled', false);

        $this->artisan('catalog-import:publish', [
            'run' => 'full-run-001',
            '--acknowledge-warnings' => true,
            '--acknowledged-by' => 'operator',
        ])
            ->expectsOutputToContain('RIMSKIE_IMPORT_PUBLICATION_ENABLED=true')
            ->assertFailed();

        $this->assertSame(0, $backup->calls);
        $this->assertNull($run->fresh()->warnings_acknowledged_at);
        $this->assertSame(0, DB::table('products')->count());
    }

    public function test_publish_and_rollback_commands_run_the_controlled_workflow(): void
    {
        $this->seedCatalogRoots();
        $run = $this->seedReviewedRun();
        $run->items()->firstOrFail()->update(['warnings' => ['reviewed wording choice']]);
        $backup = new CommandTestBackupService($this->backupDirectory);
        $sitemap = new CommandTestSitemapGenerator;
        $this->app->instance(DatabaseBackupService::class, $backup);
        $this->app->instance(CatalogImportSitemapGenerator::class, $sitemap);
        config()->set('catalog-import-publication.enabled', true);

        $this->artisan('catalog-import:publish', [
            'run' => 'full-run-001',
            '--acknowledge-warnings' => true,
            '--acknowledged-by' => 'release-operator',
        ])
            ->expectsOutputToContain('Published run=full-run-001 no_op=no sitemap=generated')
            ->assertSuccessful();

        $this->assertSame('release-operator', $run->fresh()->warnings_acknowledged_by);
        $this->assertSame(1, $backup->calls);
        $this->assertSame(CatalogImportRun::STATUS_PUBLISHED, $run->fresh()->status);

        $this->artisan('catalog-import:rollback', ['run' => (string) $run->id])
            ->expectsOutputToContain('Rolled back run=full-run-001 no_op=no sitemap=generated')
            ->assertSuccessful();

        $this->assertSame(CatalogImportRun::STATUS_ROLLED_BACK, $run->fresh()->status);
        $this->assertSame(0, DB::table('products')->where('import_run_id', $run->id)->count());
        $this->assertSame(2, $sitemap->calls);
    }
}

final class CommandTestBackupService extends DatabaseBackupService
{
    public int $calls = 0;

    public function __construct(
        private readonly string $directory,
        private readonly ?\Closure $beforeCreate = null,
        private readonly bool $fail = false,
    ) {
        parent::__construct(
            runner: new class implements DatabaseDumpRunner
            {
                public function run(DatabaseBackupInvocation $invocation): void
                {
                    throw new \LogicException('Unused overridden command backup runner.');
                }
            },
            archive: new GzipBackupArchive,
            destination: $directory,
            publicRoots: [sys_get_temp_dir().DIRECTORY_SEPARATOR.'unrelated-public-root'],
        );
    }

    public function create(DatabaseBackupRequest $request): VerifiedDatabaseBackup
    {
        $this->calls++;
        if ($this->beforeCreate !== null) {
            ($this->beforeCreate)($request);
        }
        if ($this->fail) {
            throw new DatabaseBackupException('SECRET_DUMP_FAILURE');
        }
        $sql = "CREATE TABLE `products` (`id` bigint);\n";
        $baseName = preg_replace('/[^a-z0-9._-]+/i', '-', $request->runId)
            .'-'.$this->calls.'-'.bin2hex(random_bytes(4));
        $archivePath = $this->directory.DIRECTORY_SEPARATOR.$baseName.'.sql.gz';
        $archive = gzopen($archivePath, 'wb6');
        gzwrite($archive, $sql);
        gzclose($archive);
        if (PHP_OS_FAMILY !== 'Windows') {
            chmod($archivePath, 0600);
        }
        $rawSha256 = hash('sha256', $sql);
        $rawSize = strlen($sql);
        $gzipSha256 = hash_file('sha256', $archivePath);
        $gzipSize = filesize($archivePath);
        $verifiedAt = new DateTimeImmutable('2026-08-26T10:15:30.123456Z');
        $manifest = [
            'schema' => 'catalog-import-database-backup',
            'version' => 1,
            'run' => ['id' => $request->runId, 'provider' => $request->provider],
            'timestamp_utc' => '2026-08-26T10:15:30.123456Z',
            'driver' => (string) ($request->connection['driver'] ?? ''),
            'connection' => [
                'name' => $request->connectionName,
                'host' => (string) ($request->connection['host'] ?? ''),
                'port' => (int) ($request->connection['port'] ?? 0),
                'database' => (string) ($request->connection['database'] ?? ''),
            ],
            'raw' => ['sha256' => $rawSha256, 'size' => $rawSize],
            'gzip' => ['sha256' => $gzipSha256, 'size' => $gzipSize],
            'verified_at' => '2026-08-26T10:15:30.123456Z',
        ];
        $manifestPath = $this->directory.DIRECTORY_SEPARATOR.$baseName.'.json';
        file_put_contents($manifestPath, json_encode($manifest, JSON_THROW_ON_ERROR));
        if (PHP_OS_FAMILY !== 'Windows') {
            chmod($manifestPath, 0600);
        }

        return new VerifiedDatabaseBackup(
            $archivePath,
            $manifestPath,
            $rawSha256,
            $rawSize,
            $gzipSha256,
            $gzipSize,
            $verifiedAt,
            $manifest,
        );
    }
}

final class CommandTestSitemapGenerator implements CatalogImportSitemapGenerator
{
    public int $calls = 0;

    public function generate(): void
    {
        $this->calls++;
    }
}
