<?php

namespace App\Services\CatalogImport\DatabaseBackup;

use App\Data\CatalogImport\BackupArtifactIdentity;
use App\Data\CatalogImport\DatabaseBackupInvocation;
use App\Data\CatalogImport\DatabaseBackupRequest;
use App\Data\CatalogImport\VerifiedDatabaseBackup;
use Closure;
use DateTimeImmutable;
use DateTimeZone;
use Throwable;

class DatabaseBackupService
{
    private readonly BackupFileDeleter $fileDeleter;

    private readonly BackupCleanupPathGuard $cleanupPathGuard;

    private readonly BackupAtomicPublisher $atomicPublisher;

    private readonly BackupPermissionHardener $permissionHardener;

    /**
     * @param  array<int, string>  $publicRoots
     */
    public function __construct(
        private readonly DatabaseDumpRunner $runner,
        private readonly GzipBackupArchive $archive,
        private readonly string $destination,
        private readonly array $publicRoots,
        private readonly ?string $binary = null,
        private readonly int $timeoutSeconds = 900,
        private readonly ?Closure $clock = null,
        ?BackupFileDeleter $fileDeleter = null,
        ?BackupCleanupPathGuard $cleanupPathGuard = null,
        ?BackupAtomicPublisher $atomicPublisher = null,
        ?BackupPermissionHardener $permissionHardener = null,
    ) {
        $this->fileDeleter = $fileDeleter ?? new NativeBackupFileDeleter;
        $this->cleanupPathGuard = $cleanupPathGuard ?? new NativeBackupCleanupPathGuard;
        $this->atomicPublisher = $atomicPublisher ?? new NativeBackupAtomicPublisher;
        $this->permissionHardener = $permissionHardener ?? new NativeBackupPermissionHardener;
    }

    public function create(DatabaseBackupRequest $request): VerifiedDatabaseBackup
    {
        $connection = $this->validateAndNormalizeRequest($request);
        $binary = $this->resolveBinary($connection['driver']);
        if (! $this->permissionHardener->isSupported()) {
            throw new DatabaseBackupException(
                'The built-in database backup permission hardener is POSIX-only; '
                .'use an externally verified private backup on Windows.',
            );
        }
        $destination = $this->prepareDestination();
        $lock = $this->acquireLock($destination);
        $ownedArtifacts = [];
        $stage = 'initialization';

        try {
            $now = $this->now();
            $baseName = $now->format('Ymd\THis.u\Z').'-'.$request->provider.'-'.$request->runId;
            $archivePath = $destination.DIRECTORY_SEPARATOR.$baseName.'.sql.gz';
            $manifestPath = $destination.DIRECTORY_SEPARATOR.$baseName.'.json';

            if ($this->pathEntryExists($archivePath) || $this->pathEntryExists($manifestPath)) {
                throw new DatabaseBackupException('A database backup with this identity already exists.');
            }

            $nonce = bin2hex(random_bytes(12));
            $rawPath = $destination.DIRECTORY_SEPARATOR.$baseName.'.'.$nonce.'.sql.tmp';
            $gzipTempPath = $destination.DIRECTORY_SEPARATOR.$baseName.'.'.$nonce.'.sql.gz.tmp';
            $manifestTempPath = $destination.DIRECTORY_SEPARATOR.$baseName.'.'.$nonce.'.json.tmp';
            $stage = 'private dump file reservation';
            $rawIdentity = $this->claimPrivateFile($rawPath, $ownedArtifacts);

            $stage = 'dump execution';
            $invocation = new DatabaseBackupInvocation(
                command: $this->buildCommand($connection, $binary),
                environment: ['MYSQL_PWD' => $connection['password']],
                outputPath: $rawPath,
                timeoutSeconds: $this->timeoutSeconds,
                outputDevice: $rawIdentity->device,
                outputInode: $rawIdentity->inode,
            );
            $this->runner->run($invocation);
            $this->assertOwnedArtifact($rawIdentity, 1);
            $this->applyPrivateFilePermissions($rawIdentity, 1);

            $stage = 'raw dump validation';
            $rawFingerprint = $this->fingerprintRawDump($rawIdentity);

            $stage = 'private archive file reservation';
            $gzipTempIdentity = $this->claimPrivateFile($gzipTempPath, $ownedArtifacts);

            $stage = 'compression';
            $this->assertOwnedArtifact($rawIdentity, 1);
            $this->assertOwnedArtifact($gzipTempIdentity, 1);
            $this->archive->compress($rawIdentity, $gzipTempIdentity);
            $this->assertOwnedArtifact($rawIdentity, 1);
            $this->assertOwnedArtifact($gzipTempIdentity, 1);
            $this->applyPrivateFilePermissions($gzipTempIdentity, 1);
            $gzipFingerprint = $this->fingerprintFile($gzipTempIdentity);

            $stage = 'archive publication';
            $archiveIdentity = $this->publishNoReplace(
                $gzipTempIdentity,
                $archivePath,
                $destination,
                $ownedArtifacts,
            );
            $this->applyPrivateFilePermissions($archiveIdentity, 1);

            $stage = 'archive verification';
            $this->assertOwnedArtifact($archiveIdentity, 1);
            $verifiedRaw = $this->archive->uncompressedFingerprint($archiveIdentity);
            $this->assertOwnedArtifact($archiveIdentity, 1);
            if ($verifiedRaw !== $rawFingerprint) {
                throw new DatabaseBackupException('The compressed database backup failed integrity verification.');
            }
            $publishedGzipFingerprint = $this->fingerprintFile($archiveIdentity);
            if ($publishedGzipFingerprint !== $gzipFingerprint) {
                throw new DatabaseBackupException('The published gzip backup changed during verification.');
            }

            $stage = 'raw dump cleanup';
            if (! $this->deleteOwnedFile($rawIdentity, $destination, 1)) {
                throw new DatabaseBackupException('Unable to remove the raw database dump.');
            }
            unset($ownedArtifacts[$rawPath]);

            $verifiedAt = $this->now();
            $manifest = $this->buildManifest(
                request: $request,
                connection: $connection,
                timestamp: $now,
                verifiedAt: $verifiedAt,
                rawFingerprint: $rawFingerprint,
                gzipFingerprint: $gzipFingerprint,
            );

            $stage = 'manifest publication';
            $manifestTempIdentity = $this->writeJsonExclusive($manifestTempPath, $manifest, $ownedArtifacts);
            $this->applyPrivateFilePermissions($manifestTempIdentity, 1);
            $manifestIdentity = $this->publishNoReplace(
                $manifestTempIdentity,
                $manifestPath,
                $destination,
                $ownedArtifacts,
            );
            $this->applyPrivateFilePermissions($manifestIdentity, 1);
            $this->assertOwnedArtifact($manifestIdentity, 1);

            return new VerifiedDatabaseBackup(
                archivePath: $archivePath,
                manifestPath: $manifestPath,
                rawSha256: $rawFingerprint['sha256'],
                rawSize: $rawFingerprint['size'],
                gzipSha256: $gzipFingerprint['sha256'],
                gzipSize: $gzipFingerprint['size'],
                verifiedAt: $verifiedAt,
                manifest: $manifest,
            );
        } catch (Throwable) {
            $cleanupFailures = $this->cleanupOwnedPaths($ownedArtifacts, $destination);
            $message = 'Database backup failed during '.$stage.'.';
            if ($cleanupFailures !== []) {
                $message .= ' Cleanup failed; manual verification is required for '
                    .count($cleanupFailures).' private artifact(s).';
            }

            throw new DatabaseBackupException($message, $cleanupFailures);
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function prepareDestination(): string
    {
        if ($this->publicRoots === []) {
            throw new DatabaseBackupException('At least one public root is required for backup path validation.');
        }

        $destinationInput = trim($this->destination);
        $this->assertAbsoluteSafePath($destinationInput, 'backup destination');
        $destinationLexical = $this->normalizePath($destinationInput);
        $publicRoots = [];
        foreach ($this->publicRoots as $publicRoot) {
            if (! is_string($publicRoot)) {
                throw new DatabaseBackupException('Every public root must be an absolute path.');
            }

            $publicRoot = trim($publicRoot);
            $this->assertAbsoluteSafePath($publicRoot, 'public root');
            $publicLexical = $this->normalizePath($publicRoot);
            if ($this->isWithin($destinationLexical, $publicLexical)) {
                throw new DatabaseBackupException('The database backup destination must not be inside a public root.');
            }

            $canonicalPublicRoot = file_exists($publicRoot) ? realpath($publicRoot) : false;
            if (file_exists($publicRoot) && (! is_dir($publicRoot) || $canonicalPublicRoot === false)) {
                throw new DatabaseBackupException('Every existing public root must resolve to a directory.');
            }

            $publicComparison = $canonicalPublicRoot === false
                ? $publicLexical
                : $this->normalizePath($canonicalPublicRoot);
            if ($this->isWithin($destinationLexical, $publicComparison)) {
                throw new DatabaseBackupException('The database backup destination must not be reachable through a public root.');
            }

            $publicRoots[] = $publicComparison;
        }

        $existingAncestor = $destinationInput;
        while (! file_exists($existingAncestor)) {
            $parent = dirname($existingAncestor);
            if ($parent === $existingAncestor) {
                throw new DatabaseBackupException('Unable to resolve a safe backup destination ancestor.');
            }
            $existingAncestor = $parent;
        }

        if (! is_dir($existingAncestor) || is_link($existingAncestor)) {
            throw new DatabaseBackupException('The database backup destination ancestor must be a real directory.');
        }

        $canonicalAncestor = realpath($existingAncestor);
        if ($canonicalAncestor === false || ! $this->pathsEqual($canonicalAncestor, $existingAncestor)) {
            throw new DatabaseBackupException('The database backup destination must not traverse a symlink or junction.');
        }

        if (! is_dir($this->destination) && ! @mkdir($this->destination, 0700, true) && ! is_dir($this->destination)) {
            throw new DatabaseBackupException('Unable to create the private database backup destination.');
        }

        if (is_link($this->destination)) {
            throw new DatabaseBackupException('The database backup destination must not be a symlink or junction.');
        }

        $destination = realpath($this->destination);
        if ($destination === false || ! $this->pathsEqual($destination, $destinationLexical)) {
            throw new DatabaseBackupException('Unable to resolve the private database backup destination.');
        }

        $canonicalDestination = $this->normalizePath($destination);
        foreach ($publicRoots as $publicRoot) {
            if ($this->isWithin($canonicalDestination, $publicRoot)) {
                throw new DatabaseBackupException('The canonical backup destination must not be inside a public root.');
            }
        }

        if (! $this->permissionHardener->secureDirectory($destination)) {
            throw new DatabaseBackupException('Unable to enforce private database backup directory permissions.');
        }
        $hardenedDestination = realpath($destination);
        if ($hardenedDestination === false
            || is_link($destination)
            || ! $this->pathsEqual($hardenedDestination, $destination)) {
            throw new DatabaseBackupException('The database backup destination changed during permission hardening.');
        }

        return $destination;
    }

    /** @return resource */
    private function acquireLock(string $destination)
    {
        $lockPath = $destination.DIRECTORY_SEPARATOR.'.catalog-import-backup.lock';
        if (! $this->cleanupPathGuard->allowsDelete($lockPath, $destination)) {
            throw new DatabaseBackupException('The database backup lock path is not safely contained.');
        }

        $existed = $this->pathEntryExists($lockPath);
        $preOpenIdentity = $existed ? $this->regularSingleLinkIdentity($lockPath) : null;
        if ($existed && $preOpenIdentity === null) {
            throw new DatabaseBackupException('The database backup lock must be a private regular file.');
        }

        $lock = $existed ? @fopen($lockPath, 'r+b') : $this->openPrivateExclusive($lockPath);
        if ($lock === false) {
            throw new DatabaseBackupException('Unable to open the database backup lock.');
        }

        $postOpenIdentity = $this->matchingLockIdentity($lock, $lockPath, $preOpenIdentity);
        if ($postOpenIdentity === null) {
            fclose($lock);
            throw new DatabaseBackupException('The database backup lock changed while it was opened.');
        }

        if (! flock($lock, LOCK_EX | LOCK_NB)) {
            fclose($lock);
            throw new DatabaseBackupException('Another database backup is already running.');
        }

        if ($this->matchingLockIdentity($lock, $lockPath, $postOpenIdentity) === null) {
            flock($lock, LOCK_UN);
            fclose($lock);
            throw new DatabaseBackupException('The database backup lock changed while it was acquired.');
        }

        $lockIdentity = new BackupArtifactIdentity(
            $lockPath,
            $postOpenIdentity['dev'],
            $postOpenIdentity['ino'],
        );
        try {
            $this->applyPrivateFilePermissions($lockIdentity, 1);
            if ($this->matchingLockIdentity($lock, $lockPath, $postOpenIdentity) === null) {
                throw new DatabaseBackupException('The database backup lock changed during permission hardening.');
            }
        } catch (Throwable $exception) {
            flock($lock, LOCK_UN);
            fclose($lock);

            throw $exception;
        }

        return $lock;
    }

    /**
     * @return array{driver: string, host: string, port: int, database: string, username: string, password: string, unix_socket: string, charset: string}
     */
    private function validateAndNormalizeRequest(DatabaseBackupRequest $request): array
    {
        $this->assertSafeSegment($request->runId, 'run id');
        $this->assertSafeSegment($request->provider, 'provider');
        $this->assertSafeSegment($request->connectionName, 'connection name');

        if ($this->timeoutSeconds < 1 || $this->timeoutSeconds > 86400) {
            throw new DatabaseBackupException('The database backup timeout is outside the allowed range.');
        }

        $connection = $request->connection;
        if (array_key_exists('url', $connection) && $connection['url'] !== null) {
            if (! is_string($connection['url']) || trim($connection['url']) !== '') {
                throw new DatabaseBackupException('Database URL connections must be resolved before backup.');
            }
        }

        $driver = strtolower($this->requiredSafeString($connection['driver'] ?? null, 'database driver', 16));
        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            throw new DatabaseBackupException('Only mysql and mariadb database backups are supported.');
        }

        $host = $this->requiredSafeString($connection['host'] ?? null, 'database host', 255);
        $database = $this->requiredSafeString($connection['database'] ?? null, 'database name', 64);
        if (preg_match('/\A[A-Za-z0-9_$][A-Za-z0-9_.$-]{0,63}\z/D', $database) !== 1) {
            throw new DatabaseBackupException('The database name is not safe for backup invocation.');
        }

        $username = $this->requiredSafeString($connection['username'] ?? null, 'database username', 128);
        $passwordValue = $connection['password'] ?? '';
        if (! is_string($passwordValue) || str_contains($passwordValue, "\0")) {
            throw new DatabaseBackupException('The database password cannot be passed safely to the dump process.');
        }

        $portValue = $connection['port'] ?? 3306;
        if ((is_string($portValue) && preg_match('/\A\d{1,5}\z/D', $portValue) !== 1)
            || (! is_string($portValue) && ! is_int($portValue))) {
            throw new DatabaseBackupException('The database port must be an integer.');
        }
        $port = (int) $portValue;
        if ($port < 1 || $port > 65535) {
            throw new DatabaseBackupException('The database port is outside the allowed range.');
        }

        $charset = $this->requiredSafeString($connection['charset'] ?? 'utf8mb4', 'database charset', 32);
        if (preg_match('/\A[A-Za-z0-9_]{1,32}\z/D', $charset) !== 1) {
            throw new DatabaseBackupException('The database charset is not safe for backup invocation.');
        }

        $socketValue = $connection['unix_socket'] ?? '';
        if (! is_string($socketValue) || preg_match('/[\x00-\x1F\x7F]/', $socketValue) === 1) {
            throw new DatabaseBackupException('The database socket is not safe for backup invocation.');
        }

        return [
            'driver' => $driver,
            'host' => $host,
            'port' => $port,
            'database' => $database,
            'username' => $username,
            'password' => $passwordValue,
            'unix_socket' => trim($socketValue),
            'charset' => $charset,
        ];
    }

    /**
     * @param  array{driver: string, host: string, port: int, database: string, username: string, password: string, unix_socket: string, charset: string}  $connection
     * @return array<int, string>
     */
    private function buildCommand(array $connection, string $binary): array
    {
        $command = [
            $binary,
            '--single-transaction',
            '--quick',
            '--routines',
            '--triggers',
            '--hex-blob',
            '--host='.$connection['host'],
            '--port='.$connection['port'],
            '--user='.$connection['username'],
            '--default-character-set='.$connection['charset'],
        ];

        if ($connection['unix_socket'] !== '') {
            $command[] = '--socket='.$connection['unix_socket'];
        }

        $command[] = '--databases';
        $command[] = $connection['database'];

        return $command;
    }

    /**
     * @param  array<string, BackupArtifactIdentity>  $ownedArtifacts
     */
    private function claimPrivateFile(string $path, array &$ownedArtifacts): BackupArtifactIdentity
    {
        $handle = $this->openPrivateExclusive($path);
        if ($handle === false) {
            throw new DatabaseBackupException('Unable to exclusively reserve the raw dump file.');
        }

        $identity = null;
        try {
            $stat = @fstat($handle);
            if (! is_array($stat) || ! $this->isRegularFileStat($stat) || $stat['nlink'] !== 1) {
                throw new DatabaseBackupException('Unable to verify the reserved database backup file.');
            }
            $identity = new BackupArtifactIdentity($path, $stat['dev'], $stat['ino']);
            $ownedArtifacts[$path] = $identity;
            if (! $this->artifactMatches($identity, 1)) {
                throw new DatabaseBackupException('The reserved database backup path changed before hardening.');
            }
            $this->applyPrivateFilePermissions($identity, 1);
            if (! fflush($handle)) {
                throw new DatabaseBackupException('Unable to initialize the raw dump file.');
            }
            if (! $this->handleMatchesArtifact($handle, $identity, 1)) {
                throw new DatabaseBackupException('The reserved database backup file identity changed.');
            }
        } finally {
            fclose($handle);
        }

        $this->assertOwnedArtifact($identity, 1);

        return $identity;
    }

    /** @return resource|false */
    private function openPrivateExclusive(string $path)
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return @fopen($path, 'x+b');
        }

        $previousMask = umask(0077);
        try {
            return @fopen($path, 'x+b');
        } finally {
            umask($previousMask);
        }
    }

    /**
     * @param  array<string, BackupArtifactIdentity>  $ownedArtifacts
     */
    private function publishNoReplace(
        BackupArtifactIdentity $source,
        string $destination,
        string $expectedDestination,
        array &$ownedArtifacts,
    ): BackupArtifactIdentity {
        if (! $this->cleanupPathGuard->allowsDelete($source->path, $expectedDestination)
            || ! $this->cleanupPathGuard->allowsDelete($destination, $expectedDestination)
            || $this->pathEntryExists($destination)
            || ! $this->artifactMatches($source, 1)
            || ! $this->atomicPublisher->link($source->path, $destination)) {
            throw new DatabaseBackupException('Unable to publish the database backup file without replacement.');
        }

        $linkedSource = new BackupArtifactIdentity($source->path, $source->device, $source->inode);
        $linkedDestination = new BackupArtifactIdentity($destination, $source->device, $source->inode);
        $ownedArtifacts[$source->path] = $linkedSource;
        $ownedArtifacts[$destination] = $linkedDestination;
        if (! $this->cleanupPathGuard->allowsDelete($source->path, $expectedDestination)
            || ! $this->cleanupPathGuard->allowsDelete($destination, $expectedDestination)) {
            throw new DatabaseBackupException('The database backup destination changed during publication.');
        }

        $linkedIdentity = $this->hardLinkedPairIdentity($source->path, $destination);
        if ($linkedIdentity === null) {
            throw new DatabaseBackupException('The published database backup file failed hard-link verification.');
        }
        if ($linkedIdentity['dev'] !== $source->device || $linkedIdentity['ino'] !== $source->inode) {
            throw new DatabaseBackupException('The published database backup file does not match the claimed source.');
        }

        if (! $this->deleteOwnedFile($linkedSource, $expectedDestination, 2)) {
            throw new DatabaseBackupException('Unable to remove the database backup publication link.');
        }
        unset($ownedArtifacts[$source->path]);

        if (! $this->cleanupPathGuard->allowsDelete($destination, $expectedDestination)) {
            throw new DatabaseBackupException('The database backup destination changed after publication.');
        }

        $publishedIdentity = $this->regularSingleLinkIdentity($destination);
        if ($publishedIdentity === null || ! $this->identitiesEqual($linkedIdentity, $publishedIdentity)) {
            throw new DatabaseBackupException('The published database backup file has an unexpected link identity.');
        }

        $ownedArtifacts[$destination] = $linkedDestination;

        return $linkedDestination;
    }

    /** @return array{dev: int, ino: int}|null */
    private function hardLinkedPairIdentity(string $source, string $destination): ?array
    {
        clearstatcache(true, $source);
        clearstatcache(true, $destination);
        $sourceStat = @lstat($source);
        $destinationStat = @lstat($destination);
        if (! is_array($sourceStat)
            || ! is_array($destinationStat)
            || is_link($source)
            || is_link($destination)
            || ! $this->isRegularFileStat($sourceStat)
            || ! $this->isRegularFileStat($destinationStat)
            || $sourceStat['nlink'] !== 2
            || $destinationStat['nlink'] !== 2
            || $sourceStat['dev'] !== $destinationStat['dev']
            || $sourceStat['ino'] !== $destinationStat['ino']) {
            return null;
        }

        return ['dev' => $sourceStat['dev'], 'ino' => $sourceStat['ino']];
    }

    /** @return array{dev: int, ino: int}|null */
    private function regularSingleLinkIdentity(string $path): ?array
    {
        clearstatcache(true, $path);
        $stat = @lstat($path);
        if (! is_array($stat)
            || is_link($path)
            || ! $this->isRegularFileStat($stat)
            || $stat['nlink'] !== 1) {
            return null;
        }

        return ['dev' => $stat['dev'], 'ino' => $stat['ino']];
    }

    /**
     * @param  resource  $handle
     * @param  array{dev: int, ino: int}|null  $expectedIdentity
     * @return array{dev: int, ino: int}|null
     */
    private function matchingLockIdentity($handle, string $path, ?array $expectedIdentity): ?array
    {
        $pathIdentity = $this->regularSingleLinkIdentity($path);
        $handleStat = @fstat($handle);
        if ($pathIdentity === null
            || ! is_array($handleStat)
            || ! $this->isRegularFileStat($handleStat)
            || $handleStat['nlink'] !== 1
            || $handleStat['dev'] !== $pathIdentity['dev']
            || $handleStat['ino'] !== $pathIdentity['ino']
            || ($expectedIdentity !== null && ! $this->identitiesEqual($pathIdentity, $expectedIdentity))) {
            return null;
        }

        return $pathIdentity;
    }

    /** @param array<int|string, mixed> $stat */
    private function isRegularFileStat(array $stat): bool
    {
        return isset($stat['mode'], $stat['nlink'], $stat['dev'], $stat['ino'])
            && is_int($stat['mode'])
            && is_int($stat['nlink'])
            && is_int($stat['dev'])
            && is_int($stat['ino'])
            && ($stat['mode'] & 0170000) === 0100000;
    }

    /**
     * @param  array{dev: int, ino: int}  $first
     * @param  array{dev: int, ino: int}  $second
     */
    private function identitiesEqual(array $first, array $second): bool
    {
        return $first['dev'] === $second['dev'] && $first['ino'] === $second['ino'];
    }

    private function assertOwnedArtifact(BackupArtifactIdentity $identity, int $expectedLinkCount): void
    {
        if (! $this->artifactMatches($identity, $expectedLinkCount)) {
            throw new DatabaseBackupException('A database backup artifact no longer matches its claimed identity.');
        }
    }

    private function artifactMatches(BackupArtifactIdentity $identity, int $expectedLinkCount): bool
    {
        clearstatcache(true, $identity->path);
        $stat = @lstat($identity->path);

        return is_array($stat)
            && ! is_link($identity->path)
            && $this->isRegularFileStat($stat)
            && $stat['nlink'] === $expectedLinkCount
            && $stat['dev'] === $identity->device
            && $stat['ino'] === $identity->inode;
    }

    /** @param resource $handle */
    private function handleMatchesArtifact($handle, BackupArtifactIdentity $identity, int $expectedLinkCount): bool
    {
        $stat = @fstat($handle);

        return is_array($stat)
            && $this->isRegularFileStat($stat)
            && $stat['nlink'] === $expectedLinkCount
            && $stat['dev'] === $identity->device
            && $stat['ino'] === $identity->inode;
    }

    private function resolveBinary(string $driver): string
    {
        $configuredBinary = trim((string) $this->binary);
        if ($configuredBinary === '') {
            return $driver === 'mariadb' ? 'mariadb-dump' : 'mysqldump';
        }

        $this->assertAbsoluteSafePath($configuredBinary, 'database dump binary');
        $resolved = realpath($configuredBinary);
        if ($resolved === false || ! is_file($resolved) || ! is_readable($resolved)) {
            throw new DatabaseBackupException('The configured database dump binary is unavailable.');
        }
        if (PHP_OS_FAMILY !== 'Windows' && ! is_executable($resolved)) {
            throw new DatabaseBackupException('The configured database dump binary is not executable.');
        }

        return $resolved;
    }

    private function assertSafeSegment(string $value, string $label): void
    {
        if (preg_match('/\A[A-Za-z0-9][A-Za-z0-9._-]{0,79}\z/D', $value) !== 1) {
            throw new DatabaseBackupException('The '.$label.' is not a safe path segment.');
        }
    }

    private function requiredSafeString(mixed $value, string $label, int $maxLength): string
    {
        if (! is_string($value)) {
            throw new DatabaseBackupException('The '.$label.' must be a string.');
        }

        $value = trim($value);
        if ($value === '' || strlen($value) > $maxLength || preg_match('/[\x00-\x1F\x7F]/', $value) === 1) {
            throw new DatabaseBackupException('The '.$label.' is missing or unsafe.');
        }

        return $value;
    }

    private function assertAbsoluteSafePath(string $path, string $label): void
    {
        if ($path === ''
            || preg_match('/[\x00-\x1F\x7F]/', $path) === 1
            || preg_match('#(?:^|[\\\\/])\.{1,2}(?:[\\\\/]|$)#', $path) === 1
            || ! $this->isAbsolutePath($path)) {
            throw new DatabaseBackupException('The '.$label.' must be an absolute path without traversal segments.');
        }
    }

    private function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, '/')
            || preg_match('/\A[A-Za-z]:[\\\\\/]/D', $path) === 1
            || preg_match('/\A[\\\\\/]{2}[^\\\\\/]+[\\\\\/][^\\\\\/]+/D', $path) === 1;
    }

    private function normalizePath(string $path): string
    {
        $normalized = str_replace('\\', '/', $path);
        $isUnc = str_starts_with($normalized, '//');
        $normalized = preg_replace('#/+#', '/', $normalized) ?? $normalized;
        if ($isUnc) {
            $normalized = '/'.$normalized;
        }
        $normalized = rtrim($normalized, '/');
        if ($normalized === '') {
            $normalized = '/';
        }

        if (PHP_OS_FAMILY === 'Windows' || preg_match('/\A[A-Za-z]:/', $normalized) === 1 || str_starts_with($normalized, '//')) {
            $normalized = strtolower($normalized);
        }

        return $normalized;
    }

    private function pathsEqual(string $first, string $second): bool
    {
        return $this->normalizePath($first) === $this->normalizePath($second);
    }

    private function isWithin(string $path, string $root): bool
    {
        $path = $this->normalizePath($path);
        $root = $this->normalizePath($root);

        return $path === $root || str_starts_with($path, rtrim($root, '/').'/');
    }

    /**
     * @return array{sha256: string, size: int}
     */
    private function fingerprintRawDump(BackupArtifactIdentity $identity): array
    {
        $fingerprint = $this->fingerprintFile($identity);
        if ($fingerprint['size'] === 0 || ! $this->containsCreateTable($identity)) {
            throw new DatabaseBackupException('The database dump is empty or contains no CREATE TABLE statement.');
        }

        return $fingerprint;
    }

    /**
     * @return array{sha256: string, size: int}
     */
    private function fingerprintFile(BackupArtifactIdentity $identity): array
    {
        $this->assertOwnedArtifact($identity, 1);
        $sha256 = @hash_file('sha256', $identity->path);
        $size = @filesize($identity->path);
        $this->assertOwnedArtifact($identity, 1);
        if (! is_string($sha256) || $size === false) {
            throw new DatabaseBackupException('Unable to fingerprint a database backup file.');
        }

        return ['sha256' => $sha256, 'size' => $size];
    }

    private function containsCreateTable(BackupArtifactIdentity $identity): bool
    {
        $this->assertOwnedArtifact($identity, 1);
        $handle = @fopen($identity->path, 'rb');
        if ($handle === false) {
            return false;
        }

        $tail = '';
        $found = false;
        try {
            if (! $this->handleMatchesArtifact($handle, $identity, 1)) {
                return false;
            }
            while (! feof($handle)) {
                $chunk = fread($handle, 1024 * 1024);
                if ($chunk === false) {
                    return false;
                }

                $search = $tail.$chunk;
                if (preg_match('/^[\t ]*CREATE[\t ]+TABLE\b/im', $search) === 1) {
                    $found = true;

                    break;
                }

                $tail = substr($search, -64);
            }
        } finally {
            fclose($handle);
        }

        $this->assertOwnedArtifact($identity, 1);

        return $found;
    }

    /**
     * @param  array{driver: string, host: string, port: int, database: string, username: string, password: string, unix_socket: string, charset: string}  $connection
     * @param  array{sha256: string, size: int}  $rawFingerprint
     * @param  array{sha256: string, size: int}  $gzipFingerprint
     * @return array<string, mixed>
     */
    private function buildManifest(
        DatabaseBackupRequest $request,
        array $connection,
        DateTimeImmutable $timestamp,
        DateTimeImmutable $verifiedAt,
        array $rawFingerprint,
        array $gzipFingerprint,
    ): array {
        $descriptor = [
            'name' => $request->connectionName,
            'host' => $connection['host'],
            'port' => $connection['port'],
            'database' => $connection['database'],
        ];
        if ($connection['unix_socket'] !== '') {
            $descriptor['unix_socket'] = $connection['unix_socket'];
        }

        return [
            'schema' => 'catalog-import-database-backup',
            'version' => 1,
            'run' => ['id' => $request->runId, 'provider' => $request->provider],
            'timestamp_utc' => $this->formatUtc($timestamp),
            'driver' => $connection['driver'],
            'connection' => $descriptor,
            'raw' => $rawFingerprint,
            'gzip' => $gzipFingerprint,
            'verified_at' => $this->formatUtc($verifiedAt),
        ];
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @param  array<string, BackupArtifactIdentity>  $ownedArtifacts
     */
    private function writeJsonExclusive(
        string $path,
        array $manifest,
        array &$ownedArtifacts,
    ): BackupArtifactIdentity {
        $json = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)."\n";
        $identity = $this->claimPrivateFile($path, $ownedArtifacts);
        $handle = @fopen($path, 'r+b');
        if ($handle === false) {
            throw new DatabaseBackupException('Unable to create the database backup manifest.');
        }

        try {
            if (! $this->handleMatchesArtifact($handle, $identity, 1)
                || ! ftruncate($handle, 0)
                || fseek($handle, 0) !== 0) {
                throw new DatabaseBackupException('Unable to initialize the database backup manifest.');
            }
            $this->applyPrivateFilePermissions($identity, 1);
            $offset = 0;
            $length = strlen($json);
            while ($offset < $length) {
                $written = fwrite($handle, substr($json, $offset));
                if ($written === false || $written === 0) {
                    throw new DatabaseBackupException('Unable to write the database backup manifest.');
                }
                $offset += $written;
            }

            if (! fflush($handle)) {
                throw new DatabaseBackupException('Unable to flush the database backup manifest.');
            }
            if (! $this->handleMatchesArtifact($handle, $identity, 1)) {
                throw new DatabaseBackupException('The database backup manifest identity changed during writing.');
            }
        } finally {
            fclose($handle);
        }

        $this->assertOwnedArtifact($identity, 1);

        return $identity;
    }

    /**
     * @param  array<string, BackupArtifactIdentity>  $artifacts
     * @return array<int, string>
     */
    private function cleanupOwnedPaths(array $artifacts, string $expectedDestination): array
    {
        $failures = [];
        foreach (array_reverse($artifacts) as $artifact) {
            $path = $artifact->path;
            if (! $this->cleanupPathGuard->allowsDelete($path, $expectedDestination)) {
                $failures[] = $path;

                continue;
            }
            if (! $this->pathEntryExists($path)) {
                continue;
            }
            $expectedLinkCount = $this->currentOwnedLinkCount($artifacts, $artifact, $expectedDestination);
            if ($expectedLinkCount < 1
                || ! $this->fileDeleter->delete(
                    $path,
                    $artifact->device,
                    $artifact->inode,
                    $expectedLinkCount,
                )) {
                $failures[] = $path;
            }
        }

        return array_reverse($failures);
    }

    private function deleteOwnedFile(
        BackupArtifactIdentity $identity,
        string $expectedDestination,
        int $expectedLinkCount,
    ): bool {
        return $this->cleanupPathGuard->allowsDelete($identity->path, $expectedDestination)
            && $this->artifactMatches($identity, $expectedLinkCount)
            && $this->fileDeleter->delete(
                $identity->path,
                $identity->device,
                $identity->inode,
                $expectedLinkCount,
            );
    }

    /**
     * @param  array<string, BackupArtifactIdentity>  $artifacts
     */
    private function currentOwnedLinkCount(
        array $artifacts,
        BackupArtifactIdentity $target,
        string $expectedDestination,
    ): int {
        $count = 0;
        foreach ($artifacts as $artifact) {
            if ($artifact->device !== $target->device
                || $artifact->inode !== $target->inode
                || ! $this->cleanupPathGuard->allowsDelete($artifact->path, $expectedDestination)) {
                continue;
            }
            clearstatcache(true, $artifact->path);
            $stat = @lstat($artifact->path);
            if (is_array($stat)
                && ! is_link($artifact->path)
                && $this->isRegularFileStat($stat)
                && $stat['dev'] === $target->device
                && $stat['ino'] === $target->inode) {
                $count++;
            }
        }

        return $count;
    }

    private function pathEntryExists(string $path): bool
    {
        return file_exists($path) || is_link($path);
    }

    private function applyPrivateFilePermissions(
        BackupArtifactIdentity $identity,
        int $expectedLinkCount,
    ): void {
        if (! $this->permissionHardener->secureFile(
            $identity->path,
            $identity->device,
            $identity->inode,
            $expectedLinkCount,
        ) || ! $this->artifactMatches($identity, $expectedLinkCount)) {
            throw new DatabaseBackupException('Unable to enforce private database backup file permissions.');
        }
    }

    private function now(): DateTimeImmutable
    {
        $now = $this->clock === null
            ? new DateTimeImmutable('now', new DateTimeZone('UTC'))
            : ($this->clock)();

        return $now->setTimezone(new DateTimeZone('UTC'));
    }

    private function formatUtc(DateTimeImmutable $dateTime): string
    {
        return $dateTime->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s.u\Z');
    }
}
