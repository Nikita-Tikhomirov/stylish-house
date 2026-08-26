<?php

namespace App\Data\CatalogImport;

final class DatabaseBackupInvocation
{
    /**
     * @param  array<int, string>  $command
     * @param  array<string, string>  $environment
     */
    public function __construct(
        public readonly array $command,
        public readonly array $environment,
        public readonly string $outputPath,
        public readonly int $timeoutSeconds,
        public readonly ?int $outputDevice = null,
        public readonly ?int $outputInode = null,
    ) {}
}
