<?php

namespace App\Data\CatalogImport;

final class DatabaseBackupRequest
{
    /**
     * @param  array<string, mixed>  $connection
     */
    public function __construct(
        public readonly string $runId,
        public readonly string $provider,
        public readonly string $connectionName,
        public readonly array $connection,
    ) {}
}
