<?php

namespace App\Services\CatalogImport\DatabaseBackup;

final class NativeBackupAtomicPublisher implements BackupAtomicPublisher
{
    public function link(string $source, string $destination): bool
    {
        return @link($source, $destination);
    }
}
