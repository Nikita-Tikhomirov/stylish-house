<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Subcategory;
use App\Support\PreviewCardData;
use Tests\TestCase;

class PreviewCardCanonicalUrlTest extends TestCase
{
    public function test_preview_payload_exposes_canonical_catalog_urls(): void
    {
        $category = new Category(['slug' => 'jaluzi']);
        $subcategory = new Subcategory(['slug' => 'gorizontalnye-zhalyuzi']);
        $product = new Product([
            'slug' => 'aliuminievye-50-mm-50-56',
            'h1' => 'Алюминиевые 50 мм 50-56',
        ]);
        $product->setRelation('category', $category);
        $product->setRelation('subcategory', $subcategory);

        $payload = PreviewCardData::fromProduct($product);

        $this->assertSame('http://localhost/jaluzi/', $payload['category_url'] ?? null);
        $this->assertSame(
            'http://localhost/jaluzi/gorizontalnye-zhalyuzi/',
            $payload['subcategory_url'] ?? null
        );
        $this->assertSame(
            'http://localhost/jaluzi/gorizontalnye-zhalyuzi/aliuminievye-50-mm-50-56/',
            $payload['product_url'] ?? null
        );
    }
}
