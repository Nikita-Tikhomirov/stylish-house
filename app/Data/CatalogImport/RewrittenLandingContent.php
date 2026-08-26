<?php

namespace App\Data\CatalogImport;

final class RewrittenLandingContent
{
    /**
     * @param  array<int, string>  $warnings
     */
    public function __construct(
        public readonly string $title,
        public readonly string $h1,
        public readonly string $intro,
        public readonly string $description,
        public readonly string $seo,
        public readonly array $warnings,
    ) {}
}
