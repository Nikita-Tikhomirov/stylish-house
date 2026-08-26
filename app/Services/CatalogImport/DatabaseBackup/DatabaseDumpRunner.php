<?php

namespace App\Services\CatalogImport\DatabaseBackup;

use App\Data\CatalogImport\DatabaseBackupInvocation;

interface DatabaseDumpRunner
{
    public function run(DatabaseBackupInvocation $invocation): void;
}
