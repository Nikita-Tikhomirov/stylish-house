<?php

namespace App\Services\CatalogImport\DatabaseBackup;

interface BackupAtomicPublisher
{
    public function link(string $source, string $destination): bool;
}
