<?php

namespace App\Data\CatalogImport;

final class BackupArtifactIdentity
{
    public function __construct(
        public readonly string $path,
        public readonly int $device,
        public readonly int $inode,
    ) {}
}
