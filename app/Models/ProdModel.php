<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProdModel extends Model
{
    use HasFactory;
    protected $table = 'prod_model';
    protected $fillable = ['title', 'image','h1'];
    public function products()
    {
        return $this->hasMany(Product::class, 'model_id'); // Указываем колонку для связи
    }
    // public function model()
    // {
    //     return $this->belongsTo(ProdModel::class, 'model_id'); // Укажите внешний ключ 'model_id'
    // }
}
