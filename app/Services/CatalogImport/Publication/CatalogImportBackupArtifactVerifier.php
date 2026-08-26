<?php

namespace App\Services\CatalogImport\Publication;

use Closure;

final class CatalogImportBackupArtifactVerifier
{
    public function __construct(private readonly ?Closure $afterOpen = null) {}

    /** @return array{bytes: string, sha256: string, size: int} */
    public function readFile(string $path, string $label): array
    {
        [$handle, $identity] = $this->openVerified($path, $label);
        $bytes = '';
        $hash = hash_init('sha256');
        $size = 0;
        try {
            while (! feof($handle)) {
                $chunk = fread($handle, 1024 * 1024);
                if ($chunk === false) {
                    $this->fail($label.' cannot be read through its verified handle');
                }
                $bytes .= $chunk;
                hash_update($hash, $chunk);
                $size += strlen($chunk);
            }
            $this->assertHandleAndPathIdentity($handle, $identity, $label);
        } catch (CatalogImportPublicationException $error) {
            throw $error;
        } catch (\Throwable $error) {
            throw new CatalogImportPublicationException(
                'Catalog import backup verification failed: '.$label.' cannot be safely read.',
                0,
                $error,
            );
        } finally {
            fclose($handle);
        }

        return ['bytes' => $bytes, 'sha256' => hash_final($hash), 'size' => $size];
    }

    /** @return array{gzip_sha256: string, gzip_size: int, raw_sha256: string, raw_size: int} */
    public function fingerprintGzip(string $path, string $label): array
    {
        [$handle, $identity] = $this->openVerified($path, $label);
        $gzipHash = hash_init('sha256');
        $rawHash = hash_init('sha256');
        $gzipSize = 0;
        $rawSize = 0;
        $inflater = inflate_init(ZLIB_ENCODING_GZIP);
        if ($inflater === false) {
            fclose($handle);
            $this->fail($label.' decompressor cannot be initialized');
        }
        try {
            while (! feof($handle)) {
                $chunk = fread($handle, 1024 * 1024);
                if ($chunk === false) {
                    $this->fail($label.' cannot be read through its verified handle');
                }
                if ($chunk === '') {
                    continue;
                }
                hash_update($gzipHash, $chunk);
                $gzipSize += strlen($chunk);
                $decoded = inflate_add($inflater, $chunk, ZLIB_SYNC_FLUSH);
                if ($decoded === false) {
                    $this->fail($label.' cannot be independently decompressed');
                }
                hash_update($rawHash, $decoded);
                $rawSize += strlen($decoded);
            }
            $tail = inflate_add($inflater, '', ZLIB_FINISH);
            if ($tail === false) {
                $this->fail($label.' cannot finish independent decompression');
            }
            hash_update($rawHash, $tail);
            $rawSize += strlen($tail);
            $this->assertHandleAndPathIdentity($handle, $identity, $label);
        } catch (CatalogImportPublicationException $error) {
            throw $error;
        } catch (\Throwable $error) {
            throw new CatalogImportPublicationException(
                'Catalog import backup verification failed: '.$label.' cannot be independently decompressed.',
                0,
                $error,
            );
        } finally {
            fclose($handle);
        }

        return [
            'gzip_sha256' => hash_final($gzipHash),
            'gzip_size' => $gzipSize,
            'raw_sha256' => hash_final($rawHash),
            'raw_size' => $rawSize,
        ];
    }

    public function assertContainedRegularFile(string $path, string $label): string
    {
        $configuredRoot = config('catalog-import-backup.destination');
        if (! is_string($configuredRoot) || ! $this->isAbsolute($configuredRoot)
            || ! $this->isAbsolute($path) || $this->hasTraversal($configuredRoot)
            || $this->hasTraversal($path)) {
            $this->fail($label.' path is not an absolute safe path');
        }

        $rootLexical = $this->normalize($configuredRoot);
        $pathLexical = $this->normalize($path);
        $rootResolved = realpath($configuredRoot);
        if ($rootResolved === false || ! is_dir($rootResolved)
            || $this->normalize($rootResolved) !== $rootLexical
            || is_link($configuredRoot)) {
            $this->fail('configured private backup root traverses a symbolic link or junction');
        }
        foreach ((array) config('catalog-import-backup.public_roots', []) as $publicRoot) {
            if (! is_string($publicRoot) || ! $this->isAbsolute($publicRoot)
                || $this->hasTraversal($publicRoot)) {
                $this->fail('configured public root is not an absolute safe path');
            }
            $publicLexical = $this->normalize($publicRoot);
            $publicResolved = file_exists($publicRoot) ? realpath($publicRoot) : false;
            $publicCanonical = $publicResolved === false
                ? $publicLexical
                : $this->normalize($publicResolved);
            if ($this->within($rootLexical, $publicLexical)
                || $this->within($rootLexical, $publicCanonical)
                || $this->within($this->normalize($rootResolved), $publicCanonical)) {
                $this->fail('configured private backup root is inside a public root');
            }
        }
        if (PHP_OS_FAMILY !== 'Windows') {
            $rootStat = @lstat($rootResolved);
            if (! is_array($rootStat) || (($rootStat['mode'] ?? 0) & 0077) !== 0
                || (function_exists('posix_geteuid') && $rootStat['uid'] !== posix_geteuid())) {
                $this->fail('configured private backup root permissions are not private');
            }
        }
        if (! $this->within($pathLexical, $rootLexical)) {
            $this->fail($label.' is outside the configured private backup root');
        }

        $relative = ltrim(substr($pathLexical, strlen($rootLexical)), DIRECTORY_SEPARATOR);
        $current = rtrim($configuredRoot, '/\\');
        foreach ($relative === '' ? [] : explode(DIRECTORY_SEPARATOR, $relative) as $part) {
            $current .= DIRECTORY_SEPARATOR.$part;
            $resolved = realpath($current);
            if ($resolved === false || is_link($current)
                || $this->normalize($resolved) !== $this->normalize($current)) {
                $this->fail($label.' traverses a symbolic link or junction');
            }
        }

        $resolved = realpath($path);
        $stat = $resolved === false ? false : @lstat($resolved);
        if ($resolved === false || ! is_file($resolved) || $stat === false
            || (($stat['mode'] ?? 0) & 0170000) !== 0100000
            || ($stat['nlink'] ?? 1) !== 1
            || ! $this->within($this->normalize($resolved), $rootLexical)) {
            $this->fail($label.' is not an exclusive regular file in the private backup root');
        }
        if (PHP_OS_FAMILY !== 'Windows'
            && ((($stat['mode'] ?? 0) & 0077) !== 0
                || (function_exists('posix_geteuid') && $stat['uid'] !== posix_geteuid()))) {
            $this->fail($label.' permissions are not private');
        }

        return $resolved;
    }

    /** @return array{resource, array{path: string, dev: int, ino: int}} */
    private function openVerified(string $path, string $label): array
    {
        $verifiedPath = $this->assertContainedRegularFile($path, $label);
        $stat = @lstat($verifiedPath);
        if (! is_array($stat) || ! isset($stat['dev'], $stat['ino'])) {
            $this->fail($label.' identity cannot be recorded');
        }
        $identity = [
            'path' => $verifiedPath,
            'dev' => (int) $stat['dev'],
            'ino' => (int) $stat['ino'],
        ];
        $handle = @fopen($verifiedPath, 'rb');
        if ($handle === false) {
            $this->fail($label.' cannot be opened through a verified handle');
        }
        try {
            $this->assertHandleAndPathIdentity($handle, $identity, $label);
            if ($this->afterOpen !== null) {
                ($this->afterOpen)($verifiedPath, $label);
            }
            $this->assertHandleAndPathIdentity($handle, $identity, $label);
        } catch (\Throwable $error) {
            fclose($handle);
            throw $error;
        }

        return [$handle, $identity];
    }

    /**
     * @param  resource  $handle
     * @param  array{path: string, dev: int, ino: int}  $identity
     */
    private function assertHandleAndPathIdentity($handle, array $identity, string $label): void
    {
        $handleStat = @fstat($handle);
        $currentPath = $this->assertContainedRegularFile($identity['path'], $label);
        $pathStat = @lstat($currentPath);
        if (! is_array($handleStat) || ! is_array($pathStat)
            || ! isset($handleStat['mode'], $handleStat['dev'], $handleStat['ino'], $handleStat['nlink'])
            || ! isset($pathStat['dev'], $pathStat['ino'])
            || ($handleStat['mode'] & 0170000) !== 0100000
            || $handleStat['nlink'] !== 1
            || (int) $handleStat['dev'] !== $identity['dev']
            || (int) $handleStat['ino'] !== $identity['ino']
            || (int) $pathStat['dev'] !== $identity['dev']
            || (int) $pathStat['ino'] !== $identity['ino']) {
            $this->fail($label.' identity changed while it was being verified');
        }
    }

    private function isAbsolute(string $path): bool
    {
        return preg_match('/^[a-z]:[\\\\\/]/iD', $path) === 1 || str_starts_with($path, '/');
    }

    private function hasTraversal(string $path): bool
    {
        return str_contains($path, "\0")
            || preg_match('~(^|[\\\\/])\.\.?(?:[\\\\/]|$)~D', $path) === 1;
    }

    private function within(string $path, string $root): bool
    {
        return $path === $root || str_starts_with($path.DIRECTORY_SEPARATOR, $root.DIRECTORY_SEPARATOR);
    }

    private function normalize(string $path): string
    {
        $normalized = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);

        return DIRECTORY_SEPARATOR === '\\' ? mb_strtolower($normalized) : $normalized;
    }

    private function fail(string $message): never
    {
        throw new CatalogImportPublicationException('Catalog import backup verification failed: '.$message.'.');
    }
}
