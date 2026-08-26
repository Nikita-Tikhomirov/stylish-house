<?php

namespace App\Services\CatalogImport\DatabaseBackup;

final class NativeBackupPermissionHardener implements BackupPermissionHardener
{
    public function isSupported(): bool
    {
        return PHP_OS_FAMILY !== 'Windows';
    }

    public function secureDirectory(string $path): bool
    {
        if (! $this->isSupported()) {
            return false;
        }

        return $this->secure($path, 0700, true);
    }

    public function secureFile(
        string $path,
        int $device,
        int $inode,
        int $expectedLinkCount,
    ): bool {
        if (! $this->isSupported()) {
            return false;
        }

        return $this->secure($path, 0600, false, $device, $inode, $expectedLinkCount);
    }

    public function enforcesPosixPermissions(): bool
    {
        return $this->isSupported();
    }

    private function secure(
        string $path,
        int $mode,
        bool $directory,
        ?int $device = null,
        ?int $inode = null,
        ?int $expectedLinkCount = null,
    ): bool {
        if (is_link($path) || ($directory ? ! is_dir($path) : ! is_file($path))) {
            return false;
        }

        if (! $directory && ! $this->matchesIdentity($path, $device, $inode, $expectedLinkCount)) {
            return false;
        }

        if (! @chmod($path, $mode)) {
            return false;
        }

        clearstatcache(true, $path);
        $stat = @lstat($path);
        if (! is_array($stat)
            || ! isset($stat['mode'], $stat['uid'])
            || ($stat['mode'] & 0077) !== 0
            || ($directory && ($stat['mode'] & 0170000) !== 0040000)
            || (! $directory && ($stat['mode'] & 0170000) !== 0100000)) {
            return false;
        }

        if (! $directory && ! $this->matchesIdentity($path, $device, $inode, $expectedLinkCount)) {
            return false;
        }

        if (function_exists('posix_geteuid')) {
            $effectiveUser = posix_geteuid();
            if (is_int($effectiveUser) && $stat['uid'] !== $effectiveUser) {
                return false;
            }
        }

        return true;
    }

    private function matchesIdentity(
        string $path,
        ?int $device,
        ?int $inode,
        ?int $expectedLinkCount,
    ): bool {
        clearstatcache(true, $path);
        $stat = @lstat($path);

        return is_array($stat)
            && ! is_link($path)
            && isset($stat['mode'], $stat['dev'], $stat['ino'], $stat['nlink'])
            && ($stat['mode'] & 0170000) === 0100000
            && $stat['dev'] === $device
            && $stat['ino'] === $inode
            && $stat['nlink'] === $expectedLinkCount;
    }
}
