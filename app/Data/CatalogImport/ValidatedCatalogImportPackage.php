<?php

namespace App\Data\CatalogImport;

final class ValidatedCatalogImportPackage
{
    /**
     * @param  array<string, mixed>  $config
     * @param  array<int, array<string, mixed>>  $sources
     * @param  array<int, array<string, mixed>>  $products
     * @param  array<int, array<string, mixed>>  $images
     * @param  array<int, array<string, string>>  $memberships
     * @param  array{sources: int, products: int, memberships: int, images: int}  $counts
     * @param  array<int, string>  $warnings
     */
    public function __construct(
        public readonly string $manifestPath,
        public readonly string $packageDirectory,
        public readonly string $schemaVersion,
        public readonly string $configSchemaVersion,
        public readonly string $runId,
        public readonly string $configDigest,
        public readonly string $manifestDigest,
        public readonly int $requestCount,
        public readonly array $config,
        public readonly array $sources,
        public readonly array $products,
        public readonly array $images,
        public readonly array $memberships,
        public readonly array $counts,
        public readonly int $pageCount,
        public readonly array $warnings = [],
    ) {}
}
