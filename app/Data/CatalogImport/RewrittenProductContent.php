<?php

namespace App\Data\CatalogImport;

final class RewrittenProductContent
{
    /**
     * @param  array<int, string>  $warnings
     */
    public function __construct(
        public readonly string $title,
        public readonly string $summary,
        public readonly string $description,
        public readonly string $slugBase,
        public readonly array $warnings,
    ) {}
}
