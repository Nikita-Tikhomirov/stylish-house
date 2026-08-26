<?php

namespace App\Data\CatalogImport;

final class QuarantinedCatalogImportImage
{
    public function __construct(
        public readonly string $publicRelativePath,
        public readonly string $trashRelativePath,
        public readonly string $sha256,
        public readonly int $byteLength,
        /** @var array{dev: int, ino: int}|null */
        public readonly ?array $fileIdentity = null,
    ) {}

    /** @return array<string, mixed> */
    public function snapshot(): array
    {
        return [
            'public_relative_path' => $this->publicRelativePath,
            'trash_relative_path' => $this->trashRelativePath,
            'sha256' => $this->sha256,
            'byte_length' => $this->byteLength,
            'file_identity' => $this->fileIdentity,
        ];
    }

    /** @param array<string, mixed> $snapshot */
    public static function fromSnapshot(array $snapshot): self
    {
        $publicPath = $snapshot['public_relative_path'] ?? null;
        $trashPath = $snapshot['trash_relative_path'] ?? null;
        $sha256 = $snapshot['sha256'] ?? null;
        $byteLength = $snapshot['byte_length'] ?? null;
        $identity = $snapshot['file_identity'] ?? null;
        $identityKeys = is_array($identity) ? array_keys($identity) : [];
        sort($identityKeys, SORT_STRING);
        if (! is_string($publicPath) || ! is_string($trashPath)
            || ! is_string($sha256) || ! preg_match('/^[a-f0-9]{64}$/D', $sha256)
            || ! is_int($byteLength) || $byteLength < 1
            || ! is_array($identity) || $identityKeys !== ['dev', 'ino']
            || ! is_int($identity['dev']) || ! is_int($identity['ino'])) {
            throw new \InvalidArgumentException('Rollback image journal entry is invalid.');
        }

        return new self($publicPath, $trashPath, $sha256, $byteLength, [
            'dev' => $identity['dev'],
            'ino' => $identity['ino'],
        ]);
    }
}
