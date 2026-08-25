<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CatalogAttributeValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'catalog_attribute_id',
        'normalized_value',
        'label',
        'numeric_value',
        'sort_order',
    ];

    protected $casts = [
        'numeric_value' => 'decimal:4',
        'sort_order' => 'integer',
    ];

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(CatalogAttribute::class, 'catalog_attribute_id');
    }

    public function importItems(): BelongsToMany
    {
        return $this->belongsToMany(
            CatalogImportItem::class,
            'catalog_import_item_attribute_value',
            'attribute_value_id',
            'import_item_id'
        )->withTimestamps();
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(
            Product::class,
            'catalog_product_attribute_value',
            'attribute_value_id',
            'product_id'
        )
            ->withPivot('catalog_import_run_id')
            ->withTimestamps();
    }
}
