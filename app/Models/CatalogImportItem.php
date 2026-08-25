<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CatalogImportItem extends Model
{
    use HasFactory;

    public const STATUS_NEEDS_REVIEW = 'needs_review';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_ERROR = 'error';

    protected $fillable = [
        'catalog_import_run_id',
        'provider',
        'external_id',
        'source_url',
        'source_title',
        'source_description',
        'source_price',
        'source_image_path',
        'rewritten_title',
        'rewritten_summary',
        'rewritten_description',
        'rewritten_slug',
        'review_status',
        'review_notes',
        'warnings',
        'error',
        'published_product_id',
        'created_product',
        'publication_snapshot',
    ];

    protected $casts = [
        'source_price' => 'decimal:2',
        'warnings' => 'array',
        'created_product' => 'boolean',
        'publication_snapshot' => 'array',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(CatalogImportRun::class, 'catalog_import_run_id');
    }

    public function sources(): BelongsToMany
    {
        return $this->belongsToMany(
            CatalogImportSource::class,
            'catalog_import_item_source',
            'import_item_id',
            'import_source_id'
        )->withTimestamps();
    }

    public function attributeValues(): BelongsToMany
    {
        return $this->belongsToMany(
            CatalogAttributeValue::class,
            'catalog_import_item_attribute_value',
            'import_item_id',
            'attribute_value_id'
        )->withTimestamps();
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'published_product_id');
    }
}
