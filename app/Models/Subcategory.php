<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Subcategory extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'slug', 'description', 'titleh1', 'first_screen_text', 'img', 'show_in_menu', 'show_in_catalog', 'seo', 'category_id', 'subcategory_id', 'start_material', 'filter_color', 'show_in_more_cats', 'show_in_cats_filter', 'menu_title', 'calc_prod', 'model_id_to_filter', 'faq_html', 'template_variant', 'plumbing_calc_title', 'plumbing_calc_subtitle', 'plumbing_calc_description', 'plumbing_calc_images', 'is_import_collection', 'import_run_id'];

    protected $casts = [
        'related_subcategory_ids' => 'array',
        'template_variant' => 'integer',
        'plumbing_calc_images' => 'array',
        'is_import_collection' => 'boolean',
        'import_run_id' => 'integer',
    ];

    // Связь принадлежности к категории
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // Связь один ко многим с продуктами
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function cloneSubcategory()
    {
        return $this->belongsTo(Subcategory::class, 'clone_subcategory_id');
    }

    public function installationTypes()
    {
        return $this->hasMany(SubcategoryInstallationType::class);
    }

    public function collectionProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'catalog_collection_product')
            ->withPivot('catalog_import_run_id')
            ->withTimestamps();
    }

    public function importRun(): BelongsTo
    {
        return $this->belongsTo(CatalogImportRun::class, 'import_run_id');
    }
}
