<?php

namespace App\Services\CatalogImport\DatabaseBackup;

final class NativeBackupFileDeleter implements BackupFileDeleter
{
    public function delete(string $path, int $device, int $inode, int $expectedLinkCount): bool
    {
        clearstatcache(true, $path);
        $stat = @lstat($path);
        if (! is_array($stat)
            || is_link($path)
            || ! isset($stat['mode'], $stat['dev'], $stat['ino'], $stat['nlink'])
            || ($stat['mode'] & 0170000) !== 0100000
            || $stat['dev'] !== $device
            || $stat['ino'] !== $inode
            || $stat['nlink'] !== $expectedLinkCount) {
            return false;
        }

        return @unlink($path);
    }
}
