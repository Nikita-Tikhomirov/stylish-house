<?php

namespace App\Services\CatalogImport\DatabaseBackup;

use App\Data\CatalogImport\BackupArtifactIdentity;

class GzipBackupArchive
{
    public function compress(
        BackupArtifactIdentity $rawIdentity,
        BackupArtifactIdentity $gzipIdentity,
    ): void {
        $source = @fopen($rawIdentity->path, 'rb');
        if ($source === false) {
            throw new DatabaseBackupException('Unable to open the raw database dump.');
        }

        $target = @fopen($gzipIdentity->path, 'r+b');
        if ($target === false) {
            fclose($source);
            throw new DatabaseBackupException('Unable to open the preclaimed gzip database backup.');
        }

        try {
            $this->assertHandleAndPathIdentity($source, $rawIdentity);
            $this->assertHandleAndPathIdentity($target, $gzipIdentity);
            if (fseek($source, 0) !== 0 || ! ftruncate($target, 0) || fseek($target, 0) !== 0) {
                throw new DatabaseBackupException('Unable to initialize the gzip database backup streams.');
            }

            $context = @deflate_init(ZLIB_ENCODING_GZIP, ['level' => 6]);
            if ($context === false) {
                throw new DatabaseBackupException('Unable to initialize gzip compression.');
            }

            while (! feof($source)) {
                $chunk = fread($source, 1024 * 1024);
                if ($chunk === false) {
                    throw new DatabaseBackupException('Unable to read the raw database dump.');
                }
                if ($chunk === '') {
                    continue;
                }

                $compressed = @deflate_add($context, $chunk, ZLIB_NO_FLUSH);
                if (! is_string($compressed)) {
                    throw new DatabaseBackupException('Unable to compress the database backup.');
                }
                $this->writeAll($target, $compressed);
            }

            $final = @deflate_add($context, '', ZLIB_FINISH);
            if (! is_string($final)) {
                throw new DatabaseBackupException('Unable to finalize the gzip database backup.');
            }
            $this->writeAll($target, $final);
            if (! fflush($target)) {
                throw new DatabaseBackupException('Unable to flush the gzip database backup.');
            }

            $this->assertHandleAndPathIdentity($source, $rawIdentity);
            $this->assertHandleAndPathIdentity($target, $gzipIdentity);
        } finally {
            fclose($source);
            fclose($target);
        }
    }

    /** @return array{sha256: string, size: int} */
    public function uncompressedFingerprint(BackupArtifactIdentity $gzipIdentity): array
    {
        $source = @fopen($gzipIdentity->path, 'rb');
        if ($source === false) {
            throw new DatabaseBackupException('Unable to open the gzip database backup.');
        }

        $hash = hash_init('sha256');
        $size = 0;

        try {
            $this->assertHandleAndPathIdentity($source, $gzipIdentity);
            $context = @inflate_init(ZLIB_ENCODING_GZIP);
            if ($context === false) {
                throw new DatabaseBackupException('Unable to initialize gzip verification.');
            }

            while (! feof($source)) {
                $chunk = fread($source, 1024 * 1024);
                if ($chunk === false) {
                    throw new DatabaseBackupException('Unable to read the gzip database backup.');
                }
                if ($chunk === '') {
                    continue;
                }

                $decoded = @inflate_add($context, $chunk, ZLIB_SYNC_FLUSH);
                if (! is_string($decoded)) {
                    throw new DatabaseBackupException('Unable to verify the gzip database backup.');
                }
                hash_update($hash, $decoded);
                $size += strlen($decoded);
            }

            $final = @inflate_add($context, '', ZLIB_FINISH);
            if (! is_string($final)) {
                throw new DatabaseBackupException('Unable to finalize gzip verification.');
            }
            hash_update($hash, $final);
            $size += strlen($final);
            $this->assertHandleAndPathIdentity($source, $gzipIdentity);
        } finally {
            fclose($source);
        }

        return ['sha256' => hash_final($hash), 'size' => $size];
    }

    /** @param resource $handle */
    private function writeAll($handle, string $contents): void
    {
        $offset = 0;
        $length = strlen($contents);
        while ($offset < $length) {
            $written = fwrite($handle, substr($contents, $offset));
            if ($written === false || $written === 0) {
                throw new DatabaseBackupException('Unable to write the gzip database backup.');
            }
            $offset += $written;
        }
    }

    /** @param resource $handle */
    private function assertHandleAndPathIdentity($handle, BackupArtifactIdentity $identity): void
    {
        $handleStat = @fstat($handle);
        clearstatcache(true, $identity->path);
        $pathStat = @lstat($identity->path);
        if (! is_array($handleStat)
            || ! is_array($pathStat)
            || is_link($identity->path)
            || ! $this->isRegularSingleLink($handleStat)
            || ! $this->isRegularSingleLink($pathStat)
            || $handleStat['dev'] !== $identity->device
            || $handleStat['ino'] !== $identity->inode
            || $pathStat['dev'] !== $identity->device
            || $pathStat['ino'] !== $identity->inode) {
            throw new DatabaseBackupException('A gzip backup stream no longer matches its claimed identity.');
        }
    }

    /** @param array<int|string, mixed> $stat */
    private function isRegularSingleLink(array $stat): bool
    {
        return isset($stat['mode'], $stat['nlink'], $stat['dev'], $stat['ino'])
            && is_int($stat['mode'])
            && is_int($stat['nlink'])
            && is_int($stat['dev'])
            && is_int($stat['ino'])
            && ($stat['mode'] & 0170000) === 0100000
            && $stat['nlink'] === 1;
    }
}
