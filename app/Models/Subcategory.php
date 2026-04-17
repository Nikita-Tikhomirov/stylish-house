<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subcategory extends Model
{
    use HasFactory;
    protected $fillable = ['title', 'slug', 'description', 'titleh1', 'first_screen_text', 'img', 'show_in_menu', 'show_in_catalog', 'seo', 'category_id', 'subcategory_id', 'start_material', 'filter_color','show_in_more_cats','show_in_cats_filter','menu_title','calc_prod','model_id_to_filter','faq_html','template_variant'];
    protected $casts = [
        'related_subcategory_ids' => 'array',
        'template_variant' => 'integer',
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
    
}
