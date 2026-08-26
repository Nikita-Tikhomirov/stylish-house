<?php

namespace App\Services\CatalogImport\DatabaseBackup;

interface BackupCleanupPathGuard
{
    public function allowsDelete(string $path, string $expectedDestination): bool;
}
