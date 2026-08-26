<?php

namespace App\Services\CatalogImport\DatabaseBackup;

interface BackupFileDeleter
{
    public function delete(string $path, int $device, int $inode, int $expectedLinkCount): bool;
}
