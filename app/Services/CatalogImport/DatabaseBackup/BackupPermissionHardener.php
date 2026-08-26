<?php

namespace App\Services\CatalogImport\DatabaseBackup;

interface BackupPermissionHardener
{
    public function isSupported(): bool;

    public function secureDirectory(string $path): bool;

    public function secureFile(
        string $path,
        int $device,
        int $inode,
        int $expectedLinkCount,
    ): bool;

    public function enforcesPosixPermissions(): bool;
}
