<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'slug', 'description', 'img', 'titleh1', 'first_screen_text', 'seo', 'related_items_ids','calc_prod','faq','subcat_title'];

    // protected $casts = [
    //     'related_category_ids' => 'array',
    // ];
    protected $casts = [
        'related_items_ids' => 'array',
    ];
    public function relatedItems(): array
    {
        $relatedIds = $this->related_items_ids ?? [];

        $categories = Category::whereIn('id', $relatedIds)->get();
        $subcategories = Subcategory::whereIn('id', $relatedIds)->get();

        return [
            'categories' => $categories,
            'subcategories' => $subcategories,
        ];
    }
    public function subcategories()
    {
        return $this->hasMany(Subcategory::class);
    }
    // Связь с товарами
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
