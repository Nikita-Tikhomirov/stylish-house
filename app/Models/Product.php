<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    protected $fillable = ['title', 'slug', 'description', 'h1', 'category_id', 'subcategory_id', 'image_path', 'image_thumb_path', 'related_product_ids', 'alternative_product_ids', 'first_screenn_description', 'color', 'model', 'coef', 'show_in_menu', 'show_in_catalog', 'seo', 'discount', 'home_actions', 'home_populars', 'model_id','fabric_id','cloth','material','characteristic','min_width','min_height','fabric_photo', 'fabric_thumb_path',
    // Параметры рольставен
    'installation_type', 'control_type', 'lock_device', 'ral_paint', 'photo_print',
    // Цены монтажа
    'overhead_price', 'builtin_price',
    // Цены управления
    'strap_price', 'cardan_price', 'pim_price', 'electric_price',
    // Цены блокирующих устройств
    'rigel_price', 'shchyolka_price', 'upper_price',
    // Цены доп опций
    'ral_price', 'photo_price'];
    // Связь с категорией (если category - это ID категории)
    // Связь с категорией
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    // Связь с подкатегорией
    public function subcategory()
    {
        return $this->belongsTo(Subcategory::class, 'subcategory_id');
    }

    public function tabs()
    {
        return $this->hasMany(Tab::class);
    }
    public function model()
    {
        return $this->belongsTo(ProdModel::class, 'model_id'); // Указываем внешний ключ 'model_id'
    }
    public function relatedProducts()
    {
        return $this->hasMany(Product::class, 'model_id', 'model_id'); // предположим, что у вас есть поле 'model_id'
    }
    public function fabric()
    {
        return $this->belongsTo(Fabric::class,'fabric_id');
    }

    protected $casts = [
        'related_product_ids' => 'array',
        'alternative_product_ids' => 'array',
        // Параметры рольставен
        'installation_type' => 'string',
        'control_type' => 'string', 
        'lock_device' => 'string',
        'ral_paint' => 'boolean',
        'photo_print' => 'boolean',
        // Цены монтажа
        'overhead_price' => 'decimal:2',
        'builtin_price' => 'decimal:2',
        // Цены управления
        'strap_price' => 'decimal:2',
        'cardan_price' => 'decimal:2',
        'pim_price' => 'decimal:2',
        'electric_price' => 'decimal:2',
        // Цены блокирующих устройств
        'rigel_price' => 'decimal:2',
        'shchyolka_price' => 'decimal:2',
        'upper_price' => 'decimal:2',
        // Цены доп опций
        'ral_price' => 'decimal:2',
        'photo_price' => 'decimal:2',
    ];
}
