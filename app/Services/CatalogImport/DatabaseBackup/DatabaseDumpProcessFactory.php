<?php

namespace App\Services\CatalogImport\DatabaseBackup;

use App\Data\CatalogImport\DatabaseBackupInvocation;
use Symfony\Component\Process\Process;

interface DatabaseDumpProcessFactory
{
    public function create(DatabaseBackupInvocation $invocation): Process;
}
