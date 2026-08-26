<?php

namespace App\Services\CatalogImport\Publication;

use Illuminate\Support\Facades\Artisan;
use RuntimeException;

final class ArtisanCatalogImportSitemapGenerator implements CatalogImportSitemapGenerator
{
    public function generate(): void
    {
        $exitCode = Artisan::call('sitemap:generate');
        if ($exitCode !== 0) {
            throw new RuntimeException('Sitemap generation exited with code '.$exitCode.'.');
        }
    }
}
