<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubcategoryInstallationType extends Model
{
    use HasFactory;

    protected $fillable = [
        'subcategory_id',
        'category_id',
        'title',
        'description',
        'image',
        'detail_image',
        'sort_order',
    ];

    public function subcategory()
    {
        return $this->belongsTo(Subcategory::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}

