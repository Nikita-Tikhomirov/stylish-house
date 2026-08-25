<?php

namespace Tests\Unit;

use App\Models\CatalogAttribute;
use App\Models\CatalogAttributeValue;
use App\Models\CatalogImportItem;
use App\Models\CatalogImportRun;
use App\Models\CatalogImportSource;
use App\Models\Product;
use App\Models\Subcategory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tests\TestCase;

class CatalogImportModelTest extends TestCase
{
    public function test_status_and_attribute_type_constants_are_stable(): void
    {
        $this->assertSame('staged', CatalogImportRun::STATUS_STAGED);
        $this->assertSame('reviewing', CatalogImportRun::STATUS_REVIEWING);
        $this->assertSame('publishing', CatalogImportRun::STATUS_PUBLISHING);
        $this->assertSame('published', CatalogImportRun::STATUS_PUBLISHED);
        $this->assertSame('rolled_back', CatalogImportRun::STATUS_ROLLED_BACK);
        $this->assertSame('error', CatalogImportRun::STATUS_ERROR);

        $this->assertSame('pending', CatalogImportSource::STATUS_PENDING);
        $this->assertSame('running', CatalogImportSource::STATUS_RUNNING);
        $this->assertSame('completed', CatalogImportSource::STATUS_COMPLETED);
        $this->assertSame('error', CatalogImportSource::STATUS_ERROR);
        $this->assertSame('needs_review', CatalogImportSource::REVIEW_NEEDS_REVIEW);
        $this->assertSame('approved', CatalogImportSource::REVIEW_APPROVED);
        $this->assertSame('rejected', CatalogImportSource::REVIEW_REJECTED);

        $this->assertSame('needs_review', CatalogImportItem::STATUS_NEEDS_REVIEW);
        $this->assertSame('approved', CatalogImportItem::STATUS_APPROVED);
        $this->assertSame('rejected', CatalogImportItem::STATUS_REJECTED);
        $this->assertSame('published', CatalogImportItem::STATUS_PUBLISHED);
        $this->assertSame('error', CatalogImportItem::STATUS_ERROR);

        $this->assertSame('select', CatalogAttribute::TYPE_SELECT);
        $this->assertSame('number', CatalogAttribute::TYPE_NUMBER);
    }

    public function test_import_models_cast_private_and_audit_data(): void
    {
        $run = new CatalogImportRun([
            'config' => ['hourly_limit' => 120],
            'started_at' => '2026-08-25 10:00:00',
        ]);
        $source = new CatalogImportSource([
            'enabled' => 1,
            'warnings' => ['short_title'],
            'created_subcategory' => 1,
            'publication_snapshot' => ['title' => 'Before'],
        ]);
        $item = new CatalogImportItem([
            'source_price' => '2708',
            'warnings' => ['removed_branding'],
            'created_product' => 1,
            'publication_snapshot' => ['title' => 'Before'],
        ]);
        $attribute = new CatalogAttribute(['is_public' => 1, 'sort_order' => '2']);
        $value = new CatalogAttributeValue(['numeric_value' => '160', 'sort_order' => '3']);

        $this->assertSame(['hourly_limit' => 120], $run->config);
        $this->assertSame('2026-08-25 10:00:00', $run->started_at->format('Y-m-d H:i:s'));
        $this->assertTrue($source->enabled);
        $this->assertSame(['short_title'], $source->warnings);
        $this->assertTrue($source->created_subcategory);
        $this->assertSame(['title' => 'Before'], $source->publication_snapshot);
        $this->assertSame('2708.00', $item->source_price);
        $this->assertSame(['removed_branding'], $item->warnings);
        $this->assertTrue($item->created_product);
        $this->assertSame(['title' => 'Before'], $item->publication_snapshot);
        $this->assertTrue($attribute->is_public);
        $this->assertSame(2, $attribute->sort_order);
        $this->assertSame('160.0000', $value->numeric_value);
        $this->assertSame(3, $value->sort_order);
    }

    public function test_models_expose_the_catalog_import_relationship_contract(): void
    {
        $run = new CatalogImportRun;
        $item = new CatalogImportItem;
        $source = new CatalogImportSource;
        $attribute = new CatalogAttribute;
        $value = new CatalogAttributeValue;
        $product = new Product;
        $subcategory = new Subcategory;

        $this->assertInstanceOf(HasMany::class, $run->sources());
        $this->assertInstanceOf(HasMany::class, $run->items());
        $this->assertInstanceOf(BelongsTo::class, $source->run());
        $this->assertInstanceOf(BelongsToMany::class, $source->items());
        $this->assertInstanceOf(BelongsTo::class, $source->publishedSubcategory());
        $this->assertInstanceOf(BelongsTo::class, $item->run());
        $this->assertInstanceOf(BelongsToMany::class, $item->sources());
        $this->assertSame('catalog_import_item_source', $item->sources()->getTable());
        $this->assertSame('import_item_id', $item->sources()->getForeignPivotKeyName());
        $this->assertSame('import_source_id', $item->sources()->getRelatedPivotKeyName());
        $this->assertInstanceOf(BelongsToMany::class, $item->attributeValues());
        $this->assertSame('catalog_import_item_attribute_value', $item->attributeValues()->getTable());
        $this->assertInstanceOf(BelongsTo::class, $item->product());
        $this->assertInstanceOf(HasMany::class, $attribute->values());
        $this->assertInstanceOf(BelongsTo::class, $value->attribute());
        $this->assertInstanceOf(BelongsToMany::class, $product->catalogCollections());
        $this->assertSame('catalog_collection_product', $product->catalogCollections()->getTable());
        $this->assertInstanceOf(BelongsToMany::class, $product->attributeValues());
        $this->assertSame('catalog_product_attribute_value', $product->attributeValues()->getTable());
        $this->assertInstanceOf(BelongsTo::class, $product->importRun());
        $this->assertInstanceOf(BelongsToMany::class, $subcategory->collectionProducts());
        $this->assertInstanceOf(BelongsTo::class, $subcategory->importRun());
    }
}
