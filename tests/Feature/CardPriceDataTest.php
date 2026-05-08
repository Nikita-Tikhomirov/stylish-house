<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProdModel;
use App\Models\Subcategory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CardPriceDataTest extends TestCase
{
    use DatabaseTransactions;

    public function test_subcategory_filter_returns_static_card_price_fields(): void
    {
        $suffix = str_replace('.', '', uniqid('card-price-', true));

        $category = Category::create([
            'title' => 'Test category ' . $suffix,
            'slug' => 'test-category-' . $suffix,
            'titleh1' => 'Category H1 ' . $suffix,
        ]);

        $subcategory = Subcategory::create([
            'title' => 'Test subcategory ' . $suffix,
            'slug' => 'test-subcategory-' . $suffix,
            'titleh1' => 'Subcategory H1 ' . $suffix,
            'category_id' => $category->id,
        ]);

        $model = ProdModel::create([
            'title' => 'Model ' . $suffix,
        ]);

        $product = Product::create([
            'title' => 'Product ' . $suffix,
            'slug' => 'test-product-' . $suffix,
            'h1' => 'Product H1 ' . $suffix,
            'category_id' => $category->id,
            'subcategory_id' => $subcategory->id,
            'model_id' => $model->id,
            'discount' => 10,
            'min_price' => 9900,
            'min_width' => 700,
            'min_height' => 800,
            'cloth' => 'Blackout',
            'image_path' => 'storage/products/main image.jpg',
            'image_thumb_path' => 'storage/products/thumb image.jpg',
            'fabric_photo' => 'storage/fabrics/fabric photo.jpg',
            'fabric_thumb_path' => 'storage/fabrics/fabric thumb.jpg',
        ]);

        $response = $this->postJson('/filter-subcat-products/' . $subcategory->id, [
            'models' => [],
            'colors' => [],
            'materials' => [],
        ]);

        $response->assertOk()
            ->assertJsonPath('products.0.id', $product->id)
            ->assertJsonPath('products.0.slug', $product->slug)
            ->assertJsonPath('products.0.h1', $product->h1)
            ->assertJsonPath('products.0.discount', 10)
            ->assertJsonPath('products.0.min_price', 9900)
            ->assertJsonPath('products.0.min_width', 700)
            ->assertJsonPath('products.0.min_height', 800)
            ->assertJsonPath('products.0.model', $model->title)
            ->assertJsonPath('products.0.modelid', $model->id)
            ->assertJsonPath('products.0.cloth', 'Blackout')
            ->assertJsonPath('products.0.category.slug', $category->slug)
            ->assertJsonPath('products.0.category.titleh1', $category->titleh1)
            ->assertJsonPath('products.0.subcategory.slug', $subcategory->slug)
            ->assertJsonPath('products.0.image_path', 'storage/products/main image.jpg')
            ->assertJsonPath('products.0.image_thumb_path', 'storage/products/thumb image.jpg')
            ->assertJsonPath('products.0.fabric_photo', asset('storage/fabrics/fabric%20photo.jpg'))
            ->assertJsonPath('products.0.fabric_thumb_path', asset('storage/fabrics/fabric%20thumb.jpg'));
    }
}
