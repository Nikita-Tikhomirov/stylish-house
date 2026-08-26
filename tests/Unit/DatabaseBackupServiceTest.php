<?php

namespace Tests\Unit;

use App\Data\CatalogImport\BackupArtifactIdentity;
use App\Data\CatalogImport\DatabaseBackupInvocation;
use App\Data\CatalogImport\DatabaseBackupRequest;
use App\Services\CatalogImport\DatabaseBackup\BackupAtomicPublisher;
use App\Services\CatalogImport\DatabaseBackup\BackupCleanupPathGuard;
use App\Services\CatalogImport\DatabaseBackup\BackupFileDeleter;
use App\Services\CatalogImport\DatabaseBackup\BackupPermissionHardener;
use App\Services\CatalogImport\DatabaseBackup\DatabaseBackupException;
use App\Services\CatalogImport\DatabaseBackup\DatabaseBackupService;
use App\Services\CatalogImport\DatabaseBackup\DatabaseDumpRunner;
use App\Services\CatalogImport\DatabaseBackup\GzipBackupArchive;
use App\Services\CatalogImport\DatabaseBackup\NativeBackupCleanupPathGuard;
use App\Services\CatalogImport\DatabaseBackup\NativeBackupFileDeleter;
use App\Services\CatalogImport\DatabaseBackup\NativeBackupPermissionHardener;
use DateTimeImmutable;
use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class DatabaseBackupServiceTest extends TestCase
{
    private string $sandbox;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sandbox = sys_get_temp_dir().DIRECTORY_SEPARATOR.'catalog-backup-test-'.bin2hex(random_bytes(8));
        mkdir($this->sandbox, 0700, true);
    }

    protected function tearDown(): void
    {
        if (str_starts_with(basename($this->sandbox), 'catalog-backup-test-')) {
            (new Filesystem)->deleteDirectory($this->sandbox);
        }

        parent::tearDown();
    }

    public function test_it_creates_and_independently_verifies_a_private_backup_without_leaking_credentials(): void
    {
        $sql = "-- controlled fixture\nCREATE TABLE `products` (`id` bigint);\nINSERT INTO `products` VALUES (1);\n";
        $runner = new RecordingDatabaseDumpRunner($sql);
        $archive = new RecordingGzipBackupArchive;
        $destination = $this->sandbox.DIRECTORY_SEPARATOR.'private'.DIRECTORY_SEPARATOR.'catalog-backups';
        $publicRoot = $this->sandbox.DIRECTORY_SEPARATOR.'public';
        mkdir($publicRoot, 0700, true);

        $service = new DatabaseBackupService(
            runner: $runner,
            archive: $archive,
            destination: $destination,
            publicRoots: [$publicRoot],
            binary: null,
            timeoutSeconds: 321,
            clock: static fn (): DateTimeImmutable => new DateTimeImmutable('2026-08-26T10:15:30.123456Z'),
            permissionHardener: new ControlledBackupPermissionHardener,
        );

        $backup = $service->create(new DatabaseBackupRequest(
            runId: 'run-20260826-001',
            provider: 'rimskie.com',
            connectionName: 'catalog-primary',
            connection: [
                'driver' => 'mysql',
                'host' => '127.0.0.1',
                'port' => 3307,
                'database' => 'stylish_house',
                'username' => 'backup_operator',
                'password' => 'super-secret-password',
                'unix_socket' => '',
                'charset' => 'utf8mb4',
            ],
        ));

        $this->assertFileExists($backup->archivePath);
        $this->assertFileExists($backup->manifestPath);
        $this->assertStringEndsWith('.sql.gz', $backup->archivePath);
        $this->assertStringEndsWith('.json', $backup->manifestPath);
        $this->assertSame($sql, $this->readGzip($backup->archivePath));
        $this->assertSame(hash('sha256', $sql), $backup->rawSha256);
        $this->assertSame(strlen($sql), $backup->rawSize);
        $this->assertSame(hash_file('sha256', $backup->archivePath), $backup->gzipSha256);
        $this->assertSame(filesize($backup->archivePath), $backup->gzipSize);
        $this->assertSame('2026-08-26T10:15:30.123456+00:00', $backup->verifiedAt->format('Y-m-d\TH:i:s.uP'));

        $this->assertNotNull($runner->invocation);
        $this->assertSame([
            'mysqldump',
            '--single-transaction',
            '--quick',
            '--routines',
            '--triggers',
            '--hex-blob',
            '--host=127.0.0.1',
            '--port=3307',
            '--user=backup_operator',
            '--default-character-set=utf8mb4',
            '--databases',
            'stylish_house',
        ], $runner->invocation->command);
        $this->assertSame(['MYSQL_PWD' => 'super-secret-password'], $runner->invocation->environment);
        $this->assertSame(321, $runner->invocation->timeoutSeconds);
        $this->assertStringEndsWith('.sql.tmp', $runner->invocation->outputPath);
        $this->assertTrue($runner->outputWasPreclaimed);
        $this->assertTrue($archive->targetWasPreclaimed);

        $manifestJson = file_get_contents($backup->manifestPath);
        $this->assertIsString($manifestJson);
        $manifest = json_decode($manifestJson, true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame($backup->manifest, $manifest);
        $this->assertSame('catalog-import-database-backup', $manifest['schema']);
        $this->assertSame(1, $manifest['version']);
        $this->assertSame(['id' => 'run-20260826-001', 'provider' => 'rimskie.com'], $manifest['run']);
        $this->assertSame('2026-08-26T10:15:30.123456Z', $manifest['timestamp_utc']);
        $this->assertSame('mysql', $manifest['driver']);
        $this->assertSame([
            'name' => 'catalog-primary',
            'host' => '127.0.0.1',
            'port' => 3307,
            'database' => 'stylish_house',
        ], $manifest['connection']);
        $this->assertSame(['sha256' => hash('sha256', $sql), 'size' => strlen($sql)], $manifest['raw']);
        $this->assertSame([
            'sha256' => hash_file('sha256', $backup->archivePath),
            'size' => filesize($backup->archivePath),
        ], $manifest['gzip']);
        $this->assertSame('2026-08-26T10:15:30.123456Z', $manifest['verified_at']);

        $serializedPublicResult = json_encode([$manifest, get_object_vars($backup)], JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString('backup_operator', $serializedPublicResult);
        $this->assertStringNotContainsString('super-secret-password', $serializedPublicResult);
        $this->assertSame([], glob($destination.DIRECTORY_SEPARATOR.'*.tmp') ?: []);
    }

    public function test_it_rejects_a_destination_inside_a_public_root_before_running_the_dump(): void
    {
        $publicRoot = $this->sandbox.DIRECTORY_SEPARATOR.'public';
        $destination = $publicRoot.DIRECTORY_SEPARATOR.'backups';
        mkdir($publicRoot, 0700, true);
        $runner = new RecordingDatabaseDumpRunner($this->validSql());

        $service = $this->service($runner, $destination, [$publicRoot]);

        try {
            $service->create($this->request());
            $this->fail('A backup destination below the web root must be rejected.');
        } catch (\Throwable) {
            $this->assertNull($runner->invocation);
            $this->assertDirectoryDoesNotExist($destination);
        }
    }

    public function test_it_rejects_a_windows_junction_or_posix_symlink_escape_without_touching_the_target_sentinel(): void
    {
        $publicRoot = $this->sandbox.DIRECTORY_SEPARATOR.'public';
        $link = $this->sandbox.DIRECTORY_SEPARATOR.'private-link';
        mkdir($publicRoot, 0700, true);
        $sentinel = $publicRoot.DIRECTORY_SEPARATOR.'outside-sentinel.txt';
        file_put_contents($sentinel, 'must remain unchanged');
        if (! $this->createDirectoryJunctionOrSymlink($publicRoot, $link)) {
            $this->markTestSkipped('This host does not permit creating a test directory junction or symlink.');
        }

        $runner = new RecordingDatabaseDumpRunner($this->validSql());
        $service = $this->service(
            $runner,
            $link.DIRECTORY_SEPARATOR.'catalog-backups',
            [$publicRoot],
        );

        try {
            $service->create($this->request());
            $this->fail('A symlink escape into the web root must be rejected.');
        } catch (\Throwable) {
            $this->assertNull($runner->invocation);
            $this->assertSame([], glob($publicRoot.DIRECTORY_SEPARATOR.'catalog-backups'.DIRECTORY_SEPARATOR.'*.gz') ?: []);
            $this->assertSame('must remain unchanged', file_get_contents($sentinel));
        } finally {
            is_link($link) ? @unlink($link) : @rmdir($link);
        }
    }

    public function test_cleanup_guard_rejects_an_ancestor_junction_swap_and_preserves_the_outside_target_sentinel(): void
    {
        $privateParent = $this->sandbox.DIRECTORY_SEPARATOR.'private';
        $destination = $privateParent.DIRECTORY_SEPARATOR.'catalog-backups';
        $outside = $this->sandbox.DIRECTORY_SEPARATOR.'outside-target';
        $outsideDestination = $outside.DIRECTORY_SEPARATOR.'catalog-backups';
        mkdir($destination, 0700, true);
        mkdir($outsideDestination, 0700, true);
        $trustedCanonicalDestination = realpath($destination);
        $this->assertIsString($trustedCanonicalDestination);
        rmdir($destination);
        rmdir($privateParent);

        $sentinelName = 'owned-looking.sql.tmp';
        $sentinel = $outsideDestination.DIRECTORY_SEPARATOR.$sentinelName;
        file_put_contents($sentinel, 'outside sentinel must survive');
        if (! $this->createDirectoryJunctionOrSymlink($outside, $privateParent)) {
            $this->markTestSkipped('This host does not permit creating a test directory junction or symlink.');
        }

        try {
            $guard = new NativeBackupCleanupPathGuard;

            $this->assertFalse($guard->allowsDelete(
                $destination.DIRECTORY_SEPARATOR.$sentinelName,
                $trustedCanonicalDestination,
            ));
            $this->assertSame('outside sentinel must survive', file_get_contents($sentinel));
        } finally {
            is_link($privateParent) ? @unlink($privateParent) : @rmdir($privateParent);
        }
    }

    public function test_it_rejects_a_public_alias_to_the_private_parent_before_creating_destination(): void
    {
        $privateParent = $this->sandbox.DIRECTORY_SEPARATOR.'private-parent';
        $publicAlias = $this->sandbox.DIRECTORY_SEPARATOR.'public-alias';
        $destination = $privateParent.DIRECTORY_SEPARATOR.'catalog-backups';
        mkdir($privateParent, 0700, true);
        if (! $this->createDirectoryJunctionOrSymlink($privateParent, $publicAlias)) {
            $this->markTestSkipped('This host does not permit creating a test directory link.');
        }

        $runner = new RecordingDatabaseDumpRunner($this->validSql());

        try {
            $this->service($runner, $destination, [$publicAlias])->create($this->request());
            $this->fail('A destination reachable through a public alias must be rejected.');
        } catch (\Throwable) {
            $this->assertNull($runner->invocation);
            $this->assertDirectoryDoesNotExist($destination);
        } finally {
            is_link($publicAlias) ? @unlink($publicAlias) : @rmdir($publicAlias);
        }
    }

    #[DataProvider('invalidConfigurationProvider')]
    public function test_it_rejects_unsafe_identifiers_connections_and_process_configuration(
        array $requestOverrides,
        array $serviceOverrides,
    ): void {
        $runner = new RecordingDatabaseDumpRunner($this->validSql());
        $connection = array_replace($this->validConnection(), $requestOverrides['connection'] ?? []);
        unset($requestOverrides['connection']);
        $request = new DatabaseBackupRequest(...array_replace([
            'runId' => 'run-20260826-001',
            'provider' => 'rimskie.com',
            'connectionName' => 'catalog-primary',
            'connection' => $connection,
        ], $requestOverrides));
        $destination = $this->sandbox.DIRECTORY_SEPARATOR.'private'.DIRECTORY_SEPARATOR.'catalog-backups';
        $publicRoot = $this->sandbox.DIRECTORY_SEPARATOR.'public';
        mkdir($publicRoot, 0700, true);
        $service = $this->service(
            runner: $runner,
            destination: $serviceOverrides['destination'] ?? $destination,
            publicRoots: $serviceOverrides['publicRoots'] ?? [$publicRoot],
            binary: $serviceOverrides['binary'] ?? null,
            timeoutSeconds: $serviceOverrides['timeoutSeconds'] ?? 900,
        );

        try {
            $service->create($request);
            $this->fail('Unsafe backup input must be rejected.');
        } catch (\Throwable) {
            $this->assertNull($runner->invocation);
            $this->assertSame([], glob($destination.DIRECTORY_SEPARATOR.'*.sql.gz') ?: []);
            $this->assertSame([], glob($destination.DIRECTORY_SEPARATOR.'*.json') ?: []);
            $this->assertDirectoryDoesNotExist(getcwd().DIRECTORY_SEPARATOR.'relative');
        }
    }

    /** @return iterable<string, array{array<string, mixed>, array<string, mixed>}> */
    public static function invalidConfigurationProvider(): iterable
    {
        yield 'path traversal in run id' => [['runId' => '../run'], []];
        yield 'separator in provider' => [['provider' => 'rimskie.com/other'], []];
        yield 'separator in connection name' => [['connectionName' => 'catalog/primary'], []];
        yield 'unsupported database driver' => [['connection' => ['driver' => 'pgsql']], []];
        yield 'option-shaped database name' => [['connection' => ['database' => '--all-databases']], []];
        yield 'empty username' => [['connection' => ['username' => '']], []];
        yield 'control character in host' => [['connection' => ['host' => "db\nhost"]], []];
        yield 'port below range' => [['connection' => ['port' => 0]], []];
        yield 'port above range' => [['connection' => ['port' => 70000]], []];
        yield 'unsafe charset' => [['connection' => ['charset' => 'utf8mb4 --skip-lock-tables']], []];
        yield 'credential-bearing database URL' => [['connection' => ['url' => 'mysql://user:secret@db/catalog']], []];
        yield 'non-string database URL' => [['connection' => ['url' => ['mysql://user:secret@db/catalog']]], []];
        yield 'relative configured binary' => [[], ['binary' => 'tools/mysqldump']];
        yield 'missing public-root policy' => [[], ['publicRoots' => []]];
        yield 'non-positive timeout' => [[], ['timeoutSeconds' => 0]];
        yield 'relative destination' => [[], ['destination' => 'relative/catalog-backups']];
    }

    public function test_it_uses_the_mariadb_executable_and_validated_absolute_binary_override(): void
    {
        $publicRoot = $this->sandbox.DIRECTORY_SEPARATOR.'public';
        $destination = $this->sandbox.DIRECTORY_SEPARATOR.'private'.DIRECTORY_SEPARATOR.'catalog-backups';
        mkdir($publicRoot, 0700, true);

        $mariadbRunner = new RecordingDatabaseDumpRunner($this->validSql());
        $this->service($mariadbRunner, $destination, [$publicRoot])->create(
            $this->request(['driver' => 'mariadb'])
        );
        $this->assertSame('mariadb-dump', $mariadbRunner->invocation?->command[0]);

        $secondDestination = $this->sandbox.DIRECTORY_SEPARATOR.'second-private'.DIRECTORY_SEPARATOR.'catalog-backups';
        $binary = $this->sandbox.DIRECTORY_SEPARATOR.(PHP_OS_FAMILY === 'Windows' ? 'mysqldump.exe' : 'mysqldump');
        file_put_contents($binary, 'controlled executable fixture');
        chmod($binary, 0700);
        $binaryRunner = new RecordingDatabaseDumpRunner($this->validSql());
        $this->service($binaryRunner, $secondDestination, [$publicRoot], $binary)->create($this->request());

        $this->assertSame(realpath($binary), $binaryRunner->invocation?->command[0]);
    }

    public function test_it_does_not_overwrite_an_existing_backup_identity(): void
    {
        $runner = new RecordingDatabaseDumpRunner($this->validSql());
        $destination = $this->sandbox.DIRECTORY_SEPARATOR.'private'.DIRECTORY_SEPARATOR.'catalog-backups';
        $publicRoot = $this->sandbox.DIRECTORY_SEPARATOR.'public';
        mkdir($publicRoot, 0700, true);
        $service = $this->service($runner, $destination, [$publicRoot]);

        $first = $service->create($this->request());
        $archiveHash = hash_file('sha256', $first->archivePath);
        $manifestHash = hash_file('sha256', $first->manifestPath);

        try {
            $service->create($this->request());
            $this->fail('A colliding backup identity must not overwrite verified artifacts.');
        } catch (\Throwable) {
            $this->assertSame(1, $runner->runs);
            $this->assertSame($archiveHash, hash_file('sha256', $first->archivePath));
            $this->assertSame($manifestHash, hash_file('sha256', $first->manifestPath));
        }
    }

    #[DataProvider('atomicPublicationRaceProvider')]
    public function test_atomic_publication_never_replaces_a_racer_owned_destination(
        int $raceOnCall,
        string $expectedSuffix,
    ): void {
        $runner = new RecordingDatabaseDumpRunner($this->validSql());
        $publisher = new RacingBackupAtomicPublisher($raceOnCall, 'racer-owned-bytes');
        $destination = $this->sandbox.DIRECTORY_SEPARATOR.'private'.DIRECTORY_SEPARATOR.'catalog-backups';
        $publicRoot = $this->sandbox.DIRECTORY_SEPARATOR.'public';
        mkdir($publicRoot, 0700, true);

        try {
            $this->service(
                $runner,
                $destination,
                [$publicRoot],
                atomicPublisher: $publisher,
            )->create($this->request());
            $this->fail('A destination created by a racer must never be replaced.');
        } catch (DatabaseBackupException $exception) {
            $this->assertStringContainsString('failed during', $exception->getMessage());
            $this->assertNotNull($publisher->racerPath);
            $this->assertStringEndsWith($expectedSuffix, $publisher->racerPath);
            $this->assertSame('racer-owned-bytes', file_get_contents($publisher->racerPath));
            $this->assertNotContains($publisher->racerPath, $exception->manualVerificationPaths);
            $this->assertSame([], glob($destination.DIRECTORY_SEPARATOR.'*.tmp') ?: []);
        }
    }

    /** @return iterable<string, array{int, string}> */
    public static function atomicPublicationRaceProvider(): iterable
    {
        yield 'archive destination race' => [1, '.sql.gz'];
        yield 'manifest destination race' => [2, '.json'];
    }

    public function test_it_fails_closed_when_an_exclusive_backup_lock_is_held(): void
    {
        $runner = new RecordingDatabaseDumpRunner($this->validSql());
        $destination = $this->sandbox.DIRECTORY_SEPARATOR.'private'.DIRECTORY_SEPARATOR.'catalog-backups';
        $publicRoot = $this->sandbox.DIRECTORY_SEPARATOR.'public';
        mkdir($destination, 0700, true);
        mkdir($publicRoot, 0700, true);
        $lock = fopen($destination.DIRECTORY_SEPARATOR.'.catalog-import-backup.lock', 'c+b');
        $this->assertIsResource($lock);
        $this->assertTrue(flock($lock, LOCK_EX | LOCK_NB));

        try {
            $this->service($runner, $destination, [$publicRoot])->create($this->request());
            $this->fail('A concurrent backup must fail closed.');
        } catch (\Throwable) {
            $this->assertNull($runner->invocation);
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    public function test_it_rejects_a_preexisting_hardlinked_lock_without_touching_the_target_sentinel(): void
    {
        $runner = new RecordingDatabaseDumpRunner($this->validSql());
        $destination = $this->sandbox.DIRECTORY_SEPARATOR.'private'.DIRECTORY_SEPARATOR.'catalog-backups';
        $publicRoot = $this->sandbox.DIRECTORY_SEPARATOR.'public';
        $sentinel = $this->sandbox.DIRECTORY_SEPARATOR.'outside-lock-sentinel.txt';
        mkdir($destination, 0700, true);
        mkdir($publicRoot, 0700, true);
        file_put_contents($sentinel, 'lock sentinel must survive');
        $this->assertTrue(link($sentinel, $destination.DIRECTORY_SEPARATOR.'.catalog-import-backup.lock'));

        try {
            $this->service($runner, $destination, [$publicRoot])->create($this->request());
            $this->fail('A hardlinked backup lock must fail closed.');
        } catch (\Throwable) {
            $this->assertNull($runner->invocation);
            $this->assertSame('lock sentinel must survive', file_get_contents($sentinel));
            $this->assertSame(2, stat($sentinel)['nlink']);
        }
    }

    public function test_runner_failures_are_sanitized_and_owned_partial_files_are_removed(): void
    {
        $runner = new LeakingFailureDatabaseDumpRunner(
            partialSql: "CREATE TABLE `partial` (`id` int);\n",
            leakedMessage: 'backup_operator super-secret-password',
        );
        $destination = $this->sandbox.DIRECTORY_SEPARATOR.'private'.DIRECTORY_SEPARATOR.'catalog-backups';
        $publicRoot = $this->sandbox.DIRECTORY_SEPARATOR.'public';
        mkdir($publicRoot, 0700, true);

        try {
            $this->service($runner, $destination, [$publicRoot])->create($this->request());
            $this->fail('The failing process runner must abort the backup.');
        } catch (\Throwable $exception) {
            $this->assertStringNotContainsString('backup_operator', $exception->getMessage());
            $this->assertStringNotContainsString('super-secret-password', $exception->getMessage());
            $this->assertSame([], glob($destination.DIRECTORY_SEPARATOR.'*.tmp') ?: []);
            $this->assertSame([], glob($destination.DIRECTORY_SEPARATOR.'*.sql.gz') ?: []);
            $this->assertSame([], glob($destination.DIRECTORY_SEPARATOR.'*.json') ?: []);
        }
    }

    public function test_service_exposes_an_injectable_permission_hardener_for_fail_closed_checks(): void
    {
        $parameterNames = array_map(
            static fn (\ReflectionParameter $parameter): string => $parameter->getName(),
            (new \ReflectionMethod(DatabaseBackupService::class, '__construct'))->getParameters(),
        );

        $this->assertContains('permissionHardener', $parameterNames);
    }

    public function test_destination_permission_hardening_failure_aborts_before_the_dump(): void
    {
        $runner = new RecordingDatabaseDumpRunner($this->validSql());
        $hardener = new ControlledBackupPermissionHardener(failDirectory: true);
        $destination = $this->sandbox.DIRECTORY_SEPARATOR.'private'.DIRECTORY_SEPARATOR.'catalog-backups';
        $publicRoot = $this->sandbox.DIRECTORY_SEPARATOR.'public';
        mkdir($publicRoot, 0700, true);

        try {
            $this->service(
                $runner,
                $destination,
                [$publicRoot],
                permissionHardener: $hardener,
            )->create($this->request());
            $this->fail('A destination that cannot be made private must abort backup creation.');
        } catch (DatabaseBackupException) {
            $this->assertNull($runner->invocation);
            $this->assertSame(1, $hardener->directoryCalls);
            $this->assertSame(0, $hardener->fileCalls);
        }
    }

    public function test_claimed_raw_file_permission_failure_aborts_before_dump_and_cleans_the_owned_file(): void
    {
        $runner = new RecordingDatabaseDumpRunner($this->validSql());
        $hardener = new ControlledBackupPermissionHardener(failFileOnCall: 2);
        $destination = $this->sandbox.DIRECTORY_SEPARATOR.'private'.DIRECTORY_SEPARATOR.'catalog-backups';
        $publicRoot = $this->sandbox.DIRECTORY_SEPARATOR.'public';
        mkdir($publicRoot, 0700, true);

        try {
            $this->service(
                $runner,
                $destination,
                [$publicRoot],
                permissionHardener: $hardener,
            )->create($this->request());
            $this->fail('A claimed raw file that cannot be made private must abort backup creation.');
        } catch (DatabaseBackupException $exception) {
            $this->assertNull($runner->invocation);
            $this->assertSame(2, $hardener->fileCalls);
            $this->assertSame([], $exception->manualVerificationPaths);
            $this->assertSame([], glob($destination.DIRECTORY_SEPARATOR.'*.tmp') ?: []);
        }
    }

    public function test_native_permission_hardener_has_an_explicit_platform_contract(): void
    {
        $directory = $this->sandbox.DIRECTORY_SEPARATOR.'permission-directory';
        $file = $directory.DIRECTORY_SEPARATOR.'permission-file';
        mkdir($directory, 0777, true);
        file_put_contents($file, 'private');
        @chmod($directory, 0777);
        @chmod($file, 0666);
        $hardener = new NativeBackupPermissionHardener;

        $this->assertSame(PHP_OS_FAMILY !== 'Windows', $hardener->enforcesPosixPermissions());
        $fileStat = stat($file);
        $this->assertIsArray($fileStat);

        if ($hardener->enforcesPosixPermissions()) {
            $this->assertTrue($hardener->secureDirectory($directory));
            $this->assertTrue($hardener->secureFile($file, $fileStat['dev'], $fileStat['ino'], 1));
            clearstatcache(true, $directory);
            clearstatcache(true, $file);
            $this->assertSame(0, fileperms($directory) & 0077);
            $this->assertSame(0, fileperms($file) & 0077);
        } else {
            $this->assertFalse($hardener->secureDirectory($directory));
            $this->assertFalse($hardener->secureFile($file, $fileStat['dev'], $fileStat['ino'], 1));
            $this->assertDirectoryExists($directory);
            $this->assertFileExists($file);
        }
    }

    public function test_native_windows_permission_hardener_is_explicitly_unsupported(): void
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            $this->markTestSkipped('The Windows fail-closed permission contract is Windows-specific.');
        }

        $directory = $this->sandbox.DIRECTORY_SEPARATOR.'native-windows-destination';
        $file = $directory.DIRECTORY_SEPARATOR.'native-windows-file';
        mkdir($directory, 0700, true);
        file_put_contents($file, 'unchanged');
        $fileStat = stat($file);
        $this->assertIsArray($fileStat);
        $hardener = new NativeBackupPermissionHardener;

        $this->assertFalse($hardener->secureDirectory($directory));
        $this->assertFalse($hardener->secureFile($file, $fileStat['dev'], $fileStat['ino'], 1));
        $this->assertSame('unchanged', file_get_contents($file));
    }

    public function test_native_windows_backup_aborts_before_lock_dump_or_artifact_write(): void
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            $this->markTestSkipped('The Windows fail-closed permission contract is Windows-specific.');
        }

        $runner = new RecordingDatabaseDumpRunner($this->validSql());
        $destination = $this->sandbox.DIRECTORY_SEPARATOR.'existing-private-destination';
        $publicRoot = $this->sandbox.DIRECTORY_SEPARATOR.'public';
        mkdir($destination, 0700, true);
        mkdir($publicRoot, 0700, true);

        try {
            $this->service(
                $runner,
                $destination,
                [$publicRoot],
                permissionHardener: new NativeBackupPermissionHardener,
            )->create($this->request());
            $this->fail('The built-in Windows permission hardener must abort backup creation.');
        } catch (DatabaseBackupException $exception) {
            $this->assertNull($runner->invocation);
            $this->assertStringContainsString('POSIX', $exception->getMessage());
            $this->assertSame([], array_values(array_diff(scandir($destination) ?: [], ['.', '..'])));
        }
    }

    public function test_compressor_path_swap_never_truncates_an_external_hardlink_target(): void
    {
        $sentinel = $this->sandbox.DIRECTORY_SEPARATOR.'external-gzip-sentinel.bin';
        $sentinelBytes = 'foreign gzip target must remain byte-for-byte';
        file_put_contents($sentinel, $sentinelBytes);
        $archive = new GzipTargetSwappingBackupArchive($sentinel);
        $runner = new RecordingDatabaseDumpRunner($this->validSql());
        $destination = $this->sandbox.DIRECTORY_SEPARATOR.'private'.DIRECTORY_SEPARATOR.'catalog-backups';
        $publicRoot = $this->sandbox.DIRECTORY_SEPARATOR.'public';
        mkdir($publicRoot, 0700, true);

        try {
            (new DatabaseBackupService(
                runner: $runner,
                archive: $archive,
                destination: $destination,
                publicRoots: [$publicRoot],
                clock: $this->clock(),
                permissionHardener: new ControlledBackupPermissionHardener,
            ))->create($this->request());
            $this->fail('A gzip target path swap must abort before truncating foreign bytes.');
        } catch (DatabaseBackupException $exception) {
            $this->assertNotNull($archive->swappedPath);
            $this->assertContains($archive->swappedPath, $exception->manualVerificationPaths);
            $this->assertFileExists($archive->swappedPath);
            $this->assertSame($sentinelBytes, file_get_contents($sentinel));
            $this->assertSame($sentinelBytes, file_get_contents($archive->swappedPath));
        }
    }

    public function test_runner_cannot_swap_the_claimed_raw_path_to_an_external_hardlink(): void
    {
        $sentinel = $this->sandbox.DIRECTORY_SEPARATOR.'external-raw-sentinel.sql';
        $sentinelBytes = $this->validSql();
        file_put_contents($sentinel, $sentinelBytes);
        $runner = new RawPathSwappingDatabaseDumpRunner($sentinel);
        $destination = $this->sandbox.DIRECTORY_SEPARATOR.'private'.DIRECTORY_SEPARATOR.'catalog-backups';
        $publicRoot = $this->sandbox.DIRECTORY_SEPARATOR.'public';
        mkdir($publicRoot, 0700, true);

        try {
            $this->service($runner, $destination, [$publicRoot])->create($this->request());
            $this->fail('A raw path replaced by a foreign hardlink must abort backup creation.');
        } catch (DatabaseBackupException $exception) {
            $this->assertNotNull($runner->swappedPath);
            $this->assertContains($runner->swappedPath, $exception->manualVerificationPaths);
            $this->assertFileExists($runner->swappedPath);
            $this->assertSame($sentinelBytes, file_get_contents($sentinel));
            $this->assertSame($sentinelBytes, file_get_contents($runner->swappedPath));
        }
    }

    public function test_deleter_path_swap_preserves_foreign_bytes_and_requires_manual_verification(): void
    {
        $sentinel = $this->sandbox.DIRECTORY_SEPARATOR.'external-cleanup-sentinel.sql';
        $sentinelBytes = 'foreign bytes must remain';
        file_put_contents($sentinel, $sentinelBytes);
        $runner = new RecordingDatabaseDumpRunner($this->validSql());
        $deleter = new RawPathSwappingBackupFileDeleter($sentinel);
        $destination = $this->sandbox.DIRECTORY_SEPARATOR.'private'.DIRECTORY_SEPARATOR.'catalog-backups';
        $publicRoot = $this->sandbox.DIRECTORY_SEPARATOR.'public';
        mkdir($publicRoot, 0700, true);

        try {
            $this->service($runner, $destination, [$publicRoot], fileDeleter: $deleter)->create($this->request());
            $this->fail('A path swap during deletion must abort verified backup creation.');
        } catch (DatabaseBackupException $exception) {
            $this->assertNotNull($deleter->swappedPath);
            $this->assertContains($deleter->swappedPath, $exception->manualVerificationPaths);
            $this->assertFileExists($deleter->swappedPath);
            $this->assertSame($sentinelBytes, file_get_contents($sentinel));
            $this->assertSame($sentinelBytes, file_get_contents($deleter->swappedPath));
        }
    }

    public function test_cleanup_preserves_a_foreign_file_swapped_over_a_published_archive(): void
    {
        $sentinel = $this->sandbox.DIRECTORY_SEPARATOR.'external-published-sentinel.bin';
        $sentinelBytes = 'foreign published bytes must remain';
        file_put_contents($sentinel, $sentinelBytes);
        $runner = new RecordingDatabaseDumpRunner($this->validSql());
        $deleter = new PublishedPathSwappingBackupFileDeleter($sentinel);
        $destination = $this->sandbox.DIRECTORY_SEPARATOR.'private'.DIRECTORY_SEPARATOR.'catalog-backups';
        $publicRoot = $this->sandbox.DIRECTORY_SEPARATOR.'public';
        mkdir($publicRoot, 0700, true);

        try {
            $this->service($runner, $destination, [$publicRoot], fileDeleter: $deleter)->create($this->request());
            $this->fail('A foreign file swapped over a published archive must be preserved for manual verification.');
        } catch (DatabaseBackupException $exception) {
            $this->assertNotNull($deleter->swappedPath);
            $this->assertContains($deleter->swappedPath, $exception->manualVerificationPaths);
            $this->assertFileExists($deleter->swappedPath);
            $this->assertSame($sentinelBytes, file_get_contents($sentinel));
            $this->assertSame($sentinelBytes, file_get_contents($deleter->swappedPath));
        }
    }

    #[DataProvider('invalidDumpProvider')]
    public function test_empty_or_structurally_invalid_dumps_are_rejected_and_cleaned(string $dump): void
    {
        $runner = new RecordingDatabaseDumpRunner($dump);
        $destination = $this->sandbox.DIRECTORY_SEPARATOR.'private'.DIRECTORY_SEPARATOR.'catalog-backups';
        $publicRoot = $this->sandbox.DIRECTORY_SEPARATOR.'public';
        mkdir($publicRoot, 0700, true);

        try {
            $this->service($runner, $destination, [$publicRoot])->create($this->request());
            $this->fail('An invalid dump must not produce a verified backup.');
        } catch (\Throwable) {
            $this->assertSame([], glob($destination.DIRECTORY_SEPARATOR.'*.tmp') ?: []);
            $this->assertSame([], glob($destination.DIRECTORY_SEPARATOR.'*.sql.gz') ?: []);
            $this->assertSame([], glob($destination.DIRECTORY_SEPARATOR.'*.json') ?: []);
        }
    }

    /** @return iterable<string, array{string}> */
    public static function invalidDumpProvider(): iterable
    {
        yield 'empty output' => [''];
        yield 'data without schema' => ["INSERT INTO `products` VALUES (1);\n"];
        yield 'comment containing separated words only' => ["-- CREATE something, then TABLE something\n"];
        yield 'comment containing a fake statement' => ["-- CREATE TABLE `not_a_real_dump` (`id` int);\n"];
    }

    public function test_a_tampered_final_archive_is_removed_and_never_gets_a_verified_manifest(): void
    {
        $runner = new RecordingDatabaseDumpRunner($this->validSql());
        $destination = $this->sandbox.DIRECTORY_SEPARATOR.'private'.DIRECTORY_SEPARATOR.'catalog-backups';
        $publicRoot = $this->sandbox.DIRECTORY_SEPARATOR.'public';
        mkdir($publicRoot, 0700, true);
        $service = new DatabaseBackupService(
            runner: $runner,
            archive: new TamperingGzipBackupArchive,
            destination: $destination,
            publicRoots: [$publicRoot],
            clock: $this->clock(),
            permissionHardener: new ControlledBackupPermissionHardener,
        );

        try {
            $service->create($this->request());
            $this->fail('A tampered final archive must fail verification.');
        } catch (\Throwable) {
            $this->assertSame([], glob($destination.DIRECTORY_SEPARATOR.'*.tmp') ?: []);
            $this->assertSame([], glob($destination.DIRECTORY_SEPARATOR.'*.sql.gz') ?: []);
            $this->assertSame([], glob($destination.DIRECTORY_SEPARATOR.'*.json') ?: []);
        }
    }

    public function test_failure_to_delete_the_raw_dump_aborts_backup_and_cleanup_is_retried(): void
    {
        $runner = new RecordingDatabaseDumpRunner($this->validSql());
        $deleter = new ControlledBackupFileDeleter(failRawDeletes: 1);
        $destination = $this->sandbox.DIRECTORY_SEPARATOR.'private'.DIRECTORY_SEPARATOR.'catalog-backups';
        $publicRoot = $this->sandbox.DIRECTORY_SEPARATOR.'public';
        mkdir($publicRoot, 0700, true);

        try {
            $this->service($runner, $destination, [$publicRoot], fileDeleter: $deleter)->create($this->request());
            $this->fail('A raw dump deletion failure must abort the verified backup.');
        } catch (DatabaseBackupException $exception) {
            $this->assertSame([], $exception->manualVerificationPaths);
            $this->assertGreaterThanOrEqual(2, $deleter->rawDeleteAttempts);
            $this->assertSame([], glob($destination.DIRECTORY_SEPARATOR.'*.tmp') ?: []);
            $this->assertSame([], glob($destination.DIRECTORY_SEPARATOR.'*.sql.gz') ?: []);
            $this->assertSame([], glob($destination.DIRECTORY_SEPARATOR.'*.json') ?: []);
        }
    }

    public function test_cleanup_failure_reports_the_exact_private_artifact_for_manual_verification(): void
    {
        $runner = new RecordingDatabaseDumpRunner($this->validSql());
        $deleter = new ControlledBackupFileDeleter(failRawDeletes: PHP_INT_MAX);
        $destination = $this->sandbox.DIRECTORY_SEPARATOR.'private'.DIRECTORY_SEPARATOR.'catalog-backups';
        $publicRoot = $this->sandbox.DIRECTORY_SEPARATOR.'public';
        mkdir($publicRoot, 0700, true);

        try {
            $this->service($runner, $destination, [$publicRoot], fileDeleter: $deleter)->create($this->request());
            $this->fail('An undeletable raw dump must require manual verification.');
        } catch (DatabaseBackupException $exception) {
            $this->assertCount(1, $exception->manualVerificationPaths);
            $this->assertStringEndsWith('.sql.tmp', $exception->manualVerificationPaths[0]);
            $this->assertFileExists($exception->manualVerificationPaths[0]);
            $this->assertStringContainsString('manual verification', $exception->getMessage());
            $this->assertStringNotContainsString('super-secret-password', $exception->getMessage());
            $this->assertSame([], glob($destination.DIRECTORY_SEPARATOR.'*.sql.gz') ?: []);
            $this->assertSame([], glob($destination.DIRECTORY_SEPARATOR.'*.json') ?: []);
        }
    }

    public function test_cleanup_preserves_artifacts_and_reports_them_when_canonical_revalidation_is_uncertain(): void
    {
        $runner = new LeakingFailureDatabaseDumpRunner(
            partialSql: $this->validSql(),
            leakedMessage: 'controlled process failure',
        );
        $guard = new RejectingBackupCleanupPathGuard(allowChecks: 1);
        $deleter = new RecordingBackupFileDeleter;
        $destination = $this->sandbox.DIRECTORY_SEPARATOR.'private'.DIRECTORY_SEPARATOR.'catalog-backups';
        $publicRoot = $this->sandbox.DIRECTORY_SEPARATOR.'public';
        mkdir($publicRoot, 0700, true);

        try {
            $this->service(
                $runner,
                $destination,
                [$publicRoot],
                fileDeleter: $deleter,
                cleanupPathGuard: $guard,
            )->create($this->request());
            $this->fail('Uncertain cleanup containment must require manual verification.');
        } catch (DatabaseBackupException $exception) {
            $this->assertGreaterThan(0, $guard->checks);
            $this->assertSame([], $deleter->paths);
            $this->assertNotSame([], $exception->manualVerificationPaths);
            $rawPaths = array_values(array_filter(
                $exception->manualVerificationPaths,
                static fn (string $path): bool => str_ends_with($path, '.sql.tmp'),
            ));
            $this->assertCount(1, $rawPaths);
            $this->assertFileExists($rawPaths[0]);
            $this->assertStringContainsString('manual verification', $exception->getMessage());
        }
    }

    public function test_non_file_process_artifact_is_reported_for_manual_verification_without_recursive_cleanup(): void
    {
        $runner = new DirectoryReplacingDatabaseDumpRunner;
        $destination = $this->sandbox.DIRECTORY_SEPARATOR.'private'.DIRECTORY_SEPARATOR.'catalog-backups';
        $publicRoot = $this->sandbox.DIRECTORY_SEPARATOR.'public';
        mkdir($publicRoot, 0700, true);

        try {
            $this->service($runner, $destination, [$publicRoot])->create($this->request());
            $this->fail('A non-file process artifact must abort backup verification.');
        } catch (DatabaseBackupException $exception) {
            $this->assertCount(1, $exception->manualVerificationPaths);
            $this->assertSame($runner->artifactPath, $exception->manualVerificationPaths[0]);
            $this->assertDirectoryExists($runner->artifactPath);
            $this->assertStringContainsString('manual verification', $exception->getMessage());
        }
    }

    private function service(
        DatabaseDumpRunner $runner,
        string $destination,
        array $publicRoots,
        ?string $binary = null,
        int $timeoutSeconds = 900,
        ?BackupFileDeleter $fileDeleter = null,
        ?BackupCleanupPathGuard $cleanupPathGuard = null,
        ?BackupAtomicPublisher $atomicPublisher = null,
        ?BackupPermissionHardener $permissionHardener = null,
    ): DatabaseBackupService {
        return new DatabaseBackupService(
            runner: $runner,
            archive: new GzipBackupArchive,
            destination: $destination,
            publicRoots: $publicRoots,
            binary: $binary,
            timeoutSeconds: $timeoutSeconds,
            clock: $this->clock(),
            fileDeleter: $fileDeleter,
            cleanupPathGuard: $cleanupPathGuard,
            atomicPublisher: $atomicPublisher,
            permissionHardener: $permissionHardener ?? new ControlledBackupPermissionHardener,
        );
    }

    private function request(array $connectionOverrides = []): DatabaseBackupRequest
    {
        return new DatabaseBackupRequest(
            runId: 'run-20260826-001',
            provider: 'rimskie.com',
            connectionName: 'catalog-primary',
            connection: array_replace($this->validConnection(), $connectionOverrides),
        );
    }

    /** @return array<string, mixed> */
    private function validConnection(): array
    {
        return [
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => 3307,
            'database' => 'stylish_house',
            'username' => 'backup_operator',
            'password' => 'super-secret-password',
            'unix_socket' => '',
            'charset' => 'utf8mb4',
        ];
    }

    private function validSql(): string
    {
        return "-- controlled fixture\nCREATE TABLE `products` (`id` bigint);\n";
    }

    private function clock(): \Closure
    {
        return static fn (): DateTimeImmutable => new DateTimeImmutable('2026-08-26T10:15:30.123456Z');
    }

    private function createDirectoryJunctionOrSymlink(string $target, string $link): bool
    {
        if (PHP_OS_FAMILY === 'Windows' && function_exists('exec')) {
            for ($attempt = 0; $attempt < 3; $attempt++) {
                $output = [];
                $exitCode = 1;
                exec('cmd.exe /d /s /c mklink /J '.escapeshellarg($link).' '.escapeshellarg($target), $output, $exitCode);
                clearstatcache(true, $link);
                if ($exitCode === 0 && is_dir($link)) {
                    return true;
                }
            }

            return false;
        }

        return @symlink($target, $link);
    }

    private function readGzip(string $path): string
    {
        $handle = gzopen($path, 'rb');
        $this->assertNotFalse($handle);

        $contents = '';
        while (! gzeof($handle)) {
            $chunk = gzread($handle, 8192);
            $this->assertNotFalse($chunk);
            $contents .= $chunk;
        }
        gzclose($handle);

        return $contents;
    }
}

final class RecordingDatabaseDumpRunner implements DatabaseDumpRunner
{
    public ?DatabaseBackupInvocation $invocation = null;

    public int $runs = 0;

    public bool $outputWasPreclaimed = false;

    public function __construct(private readonly string $sql) {}

    public function run(DatabaseBackupInvocation $invocation): void
    {
        $this->runs++;
        $this->invocation = $invocation;
        $this->outputWasPreclaimed = is_file($invocation->outputPath)
            && filesize($invocation->outputPath) === 0;
        file_put_contents($invocation->outputPath, $this->sql);
    }
}

final class LeakingFailureDatabaseDumpRunner implements DatabaseDumpRunner
{
    public function __construct(
        private readonly string $partialSql,
        private readonly string $leakedMessage,
    ) {}

    public function run(DatabaseBackupInvocation $invocation): void
    {
        file_put_contents($invocation->outputPath, $this->partialSql);

        throw new RuntimeException($this->leakedMessage);
    }
}

final class DirectoryReplacingDatabaseDumpRunner implements DatabaseDumpRunner
{
    public string $artifactPath = '';

    public function run(DatabaseBackupInvocation $invocation): void
    {
        $this->artifactPath = $invocation->outputPath;
        unlink($invocation->outputPath);
        mkdir($invocation->outputPath, 0700);
    }
}

final class RawPathSwappingDatabaseDumpRunner implements DatabaseDumpRunner
{
    public ?string $swappedPath = null;

    public function __construct(private readonly string $sentinelPath) {}

    public function run(DatabaseBackupInvocation $invocation): void
    {
        $this->swappedPath = $invocation->outputPath;
        unlink($invocation->outputPath);
        link($this->sentinelPath, $invocation->outputPath);
    }
}

final class TamperingGzipBackupArchive extends GzipBackupArchive
{
    public function uncompressedFingerprint(BackupArtifactIdentity $gzipIdentity): array
    {
        $gzipPath = $gzipIdentity->path;
        $contents = file_get_contents($gzipPath);
        if (is_string($contents) && strlen($contents) > 7) {
            // Change only the gzip mtime header: raw SQL still decompresses byte-for-byte.
            $contents[4] = $contents[4] === "\0" ? "\1" : "\0";
            file_put_contents($gzipPath, $contents);
        }

        return parent::uncompressedFingerprint($gzipIdentity);
    }
}

final class RecordingGzipBackupArchive extends GzipBackupArchive
{
    public bool $targetWasPreclaimed = false;

    public function compress(
        BackupArtifactIdentity $rawIdentity,
        BackupArtifactIdentity $gzipIdentity,
    ): void {
        $this->targetWasPreclaimed = is_file($gzipIdentity->path) && filesize($gzipIdentity->path) === 0;

        parent::compress($rawIdentity, $gzipIdentity);
    }
}

final class GzipTargetSwappingBackupArchive extends GzipBackupArchive
{
    public ?string $swappedPath = null;

    public function __construct(private readonly string $sentinelPath) {}

    public function compress(
        BackupArtifactIdentity $rawIdentity,
        BackupArtifactIdentity $gzipIdentity,
    ): void {
        $this->swappedPath = $gzipIdentity->path;
        unlink($gzipIdentity->path);
        link($this->sentinelPath, $gzipIdentity->path);

        parent::compress($rawIdentity, $gzipIdentity);
    }
}

final class ControlledBackupFileDeleter implements BackupFileDeleter
{
    public int $rawDeleteAttempts = 0;

    public function __construct(private int $failRawDeletes) {}

    public function delete(string $path, int $device, int $inode, int $expectedLinkCount): bool
    {
        if (str_ends_with($path, '.sql.tmp')) {
            $this->rawDeleteAttempts++;
            if ($this->rawDeleteAttempts <= $this->failRawDeletes) {
                return false;
            }
        }

        return (new NativeBackupFileDeleter)->delete($path, $device, $inode, $expectedLinkCount);
    }
}

final class RawPathSwappingBackupFileDeleter implements BackupFileDeleter
{
    public ?string $swappedPath = null;

    public function __construct(private readonly string $sentinelPath) {}

    public function delete(string $path, int $device, int $inode, int $expectedLinkCount): bool
    {
        if ($this->swappedPath === null && str_ends_with($path, '.sql.tmp')) {
            $this->swappedPath = $path;
            unlink($path);
            link($this->sentinelPath, $path);
        }

        return (new NativeBackupFileDeleter)->delete($path, $device, $inode, $expectedLinkCount);
    }
}

final class PublishedPathSwappingBackupFileDeleter implements BackupFileDeleter
{
    public ?string $swappedPath = null;

    private bool $rawFailureInjected = false;

    public function __construct(private readonly string $sentinelPath) {}

    public function delete(string $path, int $device, int $inode, int $expectedLinkCount): bool
    {
        if (! $this->rawFailureInjected && str_ends_with($path, '.sql.tmp')) {
            $this->rawFailureInjected = true;

            return false;
        }

        if ($this->swappedPath === null && str_ends_with($path, '.sql.gz')) {
            $this->swappedPath = $path;
            unlink($path);
            link($this->sentinelPath, $path);
        }

        return (new NativeBackupFileDeleter)->delete($path, $device, $inode, $expectedLinkCount);
    }
}

final class RejectingBackupCleanupPathGuard implements BackupCleanupPathGuard
{
    public int $checks = 0;

    public function __construct(private readonly int $allowChecks = 0) {}

    public function allowsDelete(string $path, string $expectedDestination): bool
    {
        $this->checks++;

        return $this->checks <= $this->allowChecks;
    }
}

final class RecordingBackupFileDeleter implements BackupFileDeleter
{
    /** @var array<int, string> */
    public array $paths = [];

    public function delete(string $path, int $device, int $inode, int $expectedLinkCount): bool
    {
        $this->paths[] = $path;

        return (new NativeBackupFileDeleter)->delete($path, $device, $inode, $expectedLinkCount);
    }
}

final class RacingBackupAtomicPublisher implements BackupAtomicPublisher
{
    public ?string $racerPath = null;

    private int $calls = 0;

    public function __construct(
        private readonly int $raceOnCall,
        private readonly string $racerContents,
    ) {}

    public function link(string $source, string $destination): bool
    {
        $this->calls++;
        if ($this->calls === $this->raceOnCall) {
            $this->racerPath = $destination;
            file_put_contents($destination, $this->racerContents);
        }

        return @link($source, $destination);
    }
}

final class ControlledBackupPermissionHardener implements BackupPermissionHardener
{
    public int $directoryCalls = 0;

    public int $fileCalls = 0;

    public function __construct(
        private readonly bool $failDirectory = false,
        private readonly ?int $failFileOnCall = null,
    ) {}

    public function isSupported(): bool
    {
        return true;
    }

    public function secureDirectory(string $path): bool
    {
        $this->directoryCalls++;

        return ! $this->failDirectory;
    }

    public function secureFile(
        string $path,
        int $device,
        int $inode,
        int $expectedLinkCount,
    ): bool {
        $this->fileCalls++;

        return $this->failFileOnCall !== $this->fileCalls;
    }

    public function enforcesPosixPermissions(): bool
    {
        return false;
    }
}
