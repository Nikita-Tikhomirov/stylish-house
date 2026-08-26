<?php

namespace App\Services\CatalogImport\DatabaseBackup;

use RuntimeException;

class DatabaseBackupException extends RuntimeException
{
    /**
     * @param  array<int, string>  $manualVerificationPaths
     */
    public function __construct(
        string $message,
        public readonly array $manualVerificationPaths = [],
    ) {
        parent::__construct($message);
    }
}
