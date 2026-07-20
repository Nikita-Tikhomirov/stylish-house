<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RollerShutterSystem extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'title',
        'image',
        'description',
        'components',
        'sort_order',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
