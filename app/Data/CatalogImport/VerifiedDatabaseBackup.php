<?php

namespace App\Data\CatalogImport;

use DateTimeImmutable;

final class VerifiedDatabaseBackup
{
    /**
     * @param  array<string, mixed>  $manifest
     */
    public function __construct(
        public readonly string $archivePath,
        public readonly string $manifestPath,
        public readonly string $rawSha256,
        public readonly int $rawSize,
        public readonly string $gzipSha256,
        public readonly int $gzipSize,
        public readonly DateTimeImmutable $verifiedAt,
        public readonly array $manifest,
    ) {}
}
