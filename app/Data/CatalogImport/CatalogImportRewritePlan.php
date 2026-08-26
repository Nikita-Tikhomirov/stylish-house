<?php

namespace App\Data\CatalogImport;

final class CatalogImportRewritePlan
{
    /**
     * @param  array<string, RewrittenLandingContent>  $landings
     * @param  array<string, RewrittenProductContent>  $products
     */
    public function __construct(
        public readonly array $landings,
        public readonly array $products,
        public readonly int $warningCount,
    ) {}
}
