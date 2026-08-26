<?php

namespace App\Data\CatalogImport;

final class CatalogImportRollbackResult
{
    public function __construct(
        public readonly bool $rolledBack,
        public readonly bool $sitemapGenerated,
        public readonly bool $noOp,
        public readonly ?string $diagnostic = null,
    ) {}
}
