<?php

namespace App\Data\CatalogImport;

final class PublishedCatalogImportImage
{
    public function __construct(
        public readonly string $relativePath,
        public readonly string $databasePath,
        public readonly string $sha256,
        public readonly int $byteLength,
        public readonly bool $created,
        /** @var array{dev: int, ino: int}|null */
        public readonly ?array $creationIdentity = null,
    ) {}

    /** @return array<string, mixed> */
    public function snapshot(): array
    {
        return [
            'relative_path' => $this->relativePath,
            'database_path' => $this->databasePath,
            'sha256' => $this->sha256,
            'byte_length' => $this->byteLength,
            'created' => $this->created,
            'creation_identity' => $this->creationIdentity,
        ];
    }
}
