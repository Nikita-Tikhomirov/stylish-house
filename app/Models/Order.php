<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'items', 'total_price'];

    protected $casts = [
        'items' => 'array', // Преобразование JSON в массив
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
