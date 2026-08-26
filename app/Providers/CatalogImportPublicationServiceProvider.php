<?php

namespace App\Providers;

use App\Services\CatalogImport\Publication\ArtisanCatalogImportSitemapGenerator;
use App\Services\CatalogImport\Publication\CatalogImportSitemapGenerator;
use Illuminate\Support\ServiceProvider;

final class CatalogImportPublicationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            CatalogImportSitemapGenerator::class,
            ArtisanCatalogImportSitemapGenerator::class,
        );
    }
}
