<?php

namespace App\Services\CatalogImport\DatabaseBackup;

use App\Data\CatalogImport\DatabaseBackupInvocation;
use Symfony\Component\Process\Process;

final class SymfonyDatabaseDumpProcessFactory implements DatabaseDumpProcessFactory
{
    public function create(DatabaseBackupInvocation $invocation): Process
    {
        return new Process(
            command: $invocation->command,
            cwd: null,
            env: $invocation->environment,
            input: null,
            timeout: $invocation->timeoutSeconds,
        );
    }
}
