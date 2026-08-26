<?php

namespace App\Data\CatalogImport;

final class CatalogImportPublicationResult
{
    public function __construct(
        public readonly bool $catalogPublished,
        public readonly bool $sitemapGenerated,
        public readonly bool $noOp,
        public readonly ?string $diagnostic = null,
    ) {}
}
