<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProdModel;
use App\Models\Subcategory;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CardPriceDataTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    public function test_subcategory_filter_returns_static_card_price_fields(): void
    {
        $entities = $this->createPreviewCardEntities();

        $response = $this->postJson('/filter-subcat-products/' . $entities['subcategory']->id, [
            'models' => [],
            'colors' => [],
            'materials' => [],
        ]);

        $response->assertOk();

        $this->assertNormalizedPreviewPayload($response->json('products.0'), $entities);
    }

    public function test_category_filter_returns_static_card_price_fields(): void
    {
        $entities = $this->createPreviewCardEntities();

        $response = $this->postJson('/filter-cat-products/' . $entities['category']->id, [
            'subcategories' => [],
            'models' => [],
            'colors' => [],
            'materials' => [],
        ]);

        $response->assertOk();

        $this->assertNormalizedPreviewPayload($response->json('products.0'), $entities);
    }

    public function test_home_products_endpoint_returns_normalized_fields_and_legacy_model_aliases(): void
    {
        $entities = $this->createPreviewCardEntities([
            'home_populars' => 'on',
        ]);

        $response = $this->getJson('/products/' . $entities['category']->id);

        $response->assertOk();

        $payload = $response->json('0');

        $this->assertNormalizedPreviewPayload($payload, $entities);
        $this->assertSame($entities['model']->title, $payload['model_title']);
        $this->assertSame($entities['model']->id, $payload['model_id']);
    }

    /**
     * @param array<string, mixed> $productOverrides
     * @return array{category: Category, subcategory: Subcategory, model: ProdModel, product: Product}
     */
    protected function createPreviewCardEntities(array $productOverrides = []): array
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

        $product = Product::create(array_merge([
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
        ], $productOverrides));

        return compact('category', 'subcategory', 'model', 'product');
    }

    /**
     * @param array<string, mixed> $payload
     * @param array{category: Category, subcategory: Subcategory, model: ProdModel, product: Product} $entities
     */
    protected function assertNormalizedPreviewPayload(array $payload, array $entities): void
    {
        $category = $entities['category'];
        $subcategory = $entities['subcategory'];
        $model = $entities['model'];
        $product = $entities['product'];

        $this->assertSame($product->id, $payload['id']);
        $this->assertSame($product->slug, $payload['slug']);
        $this->assertSame($product->h1, $payload['h1']);
        $this->assertSame(10, $payload['discount']);
        $this->assertSame(9900, $payload['min_price']);
        $this->assertSame(700, $payload['min_width']);
        $this->assertSame(800, $payload['min_height']);
        $this->assertSame($model->title, $payload['model']);
        $this->assertSame($model->id, $payload['modelid']);
        $this->assertSame('Blackout', $payload['cloth']);
        $this->assertSame($category->slug, $payload['category']['slug']);
        $this->assertSame($category->titleh1, $payload['category']['titleh1']);
        $this->assertSame($subcategory->slug, $payload['subcategory']['slug']);
        $this->assertSame('storage/products/main image.jpg', $payload['image_path']);
        $this->assertSame('storage/products/thumb image.jpg', $payload['image_thumb_path']);
        $this->assertSame(asset('storage/fabrics/fabric%20photo.jpg'), $payload['fabric_photo']);
        $this->assertSame(asset('storage/fabrics/fabric%20thumb.jpg'), $payload['fabric_thumb_path']);
    }
}
