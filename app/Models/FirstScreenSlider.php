<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FirstScreenSlider extends Model
{
    use HasFactory;
    protected $fillable = ['subtitle','title','description_start','description_colored','description_end','product_id','model_id', ];
    public function product()
{
    return $this->belongsTo(Product::class, 'product_id');
}

}
