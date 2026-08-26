<?php

namespace App\Services\CatalogImport;

use App\Data\CatalogImport\RewrittenLandingContent;

interface LandingContentRewriter
{
    public function rewrite(string $label, string $targetSlug): RewrittenLandingContent;
}
