<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tab extends Model
{
    use HasFactory;
    protected $fillable = ['title','tab','product_id'];
    public function subcategory()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
