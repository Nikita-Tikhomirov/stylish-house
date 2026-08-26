<?php

namespace App\Services\CatalogImport;

use App\Data\CatalogImport\RewrittenProductContent;

interface ProductContentRewriter
{
    /**
     * @param  array<string, mixed>  $source
     */
    public function rewrite(array $source): RewrittenProductContent;
}
