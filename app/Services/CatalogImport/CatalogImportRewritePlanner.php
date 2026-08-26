<?php

namespace App\Services\CatalogImport;

use App\Data\CatalogImport\CatalogImportRewritePlan;
use App\Data\CatalogImport\RewrittenLandingContent;
use App\Data\CatalogImport\ValidatedCatalogImportPackage;

final class CatalogImportRewritePlanner
{
    private ProductContentRewriter $productRewriter;

    private LandingContentRewriter $landingRewriter;

    public function __construct(
        ?ProductContentRewriter $productRewriter = null,
        ?LandingContentRewriter $landingRewriter = null,
        ?PublicTextSanitizer $sanitizer = null,
    ) {
        $sanitizer ??= new PublicTextSanitizer;
        $this->productRewriter = $productRewriter ?? new TemplateProductRewriter($sanitizer);
        $this->landingRewriter = $landingRewriter ?? new TemplateLandingRewriter($sanitizer);
    }

    public function plan(ValidatedCatalogImportPackage $package): CatalogImportRewritePlan
    {
        $landings = [];
        $signatures = [];
        foreach ($package->sources as $source) {
            $slug = $source['target_slug'];
            $draft = $this->landingRewriter->rewrite($source['label'], $slug);
            $landings[$slug] = $draft;
            $signatures[$this->landingSignature($draft)][] = $slug;
        }
        foreach ($signatures as $slugs) {
            if (count($slugs) < 2) {
                continue;
            }
            foreach ($slugs as $slug) {
                $draft = $landings[$slug];
                $warnings = [...$draft->warnings, 'duplicate_landing_copy'];
                $warnings = array_values(array_unique($warnings));
                sort($warnings, SORT_STRING);
                $landings[$slug] = new RewrittenLandingContent(
                    title: $draft->title,
                    h1: $draft->h1,
                    intro: $draft->intro,
                    description: $draft->description,
                    seo: $draft->seo,
                    warnings: $warnings,
                );
            }
        }

        $products = [];
        foreach ($package->products as $product) {
            $products[$product['external_id']] = $this->productRewriter->rewrite([
                'external_id' => $product['external_id'],
                'title' => $product['source_title'],
                'description' => $product['source_description'],
                'source_price' => $product['source_price'],
                'attributes' => $product['attributes'],
            ]);
        }

        $warningCount = count($package->warnings);
        foreach ($landings as $draft) {
            $warningCount += count($draft->warnings);
        }
        foreach ($products as $draft) {
            $warningCount += count($draft->warnings);
        }

        return new CatalogImportRewritePlan($landings, $products, $warningCount);
    }

    private function landingSignature(RewrittenLandingContent $draft): string
    {
        $value = implode("\0", [
            $draft->title,
            $draft->h1,
            $draft->intro,
            $draft->description,
            $draft->seo,
        ]);
        $value = mb_strtolower($value);

        return preg_replace('/\s+/u', ' ', trim($value)) ?? trim($value);
    }
}
