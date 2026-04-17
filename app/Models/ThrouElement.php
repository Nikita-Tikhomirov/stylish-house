<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ThrouElement extends Model
{
    use HasFactory;

    /**
     * Таблица, связанная с моделью.
     *
     * @var string
     */
    protected $table = 'throu_elements';

    /**
     * Атрибуты, которые можно массово заполнять.
     *
     * @var array
     */
    protected $casts = [
        'curtain_subcategories' => 'array',
        'blind_subcategories' => 'array',
    ];

    protected $fillable = [
        'logo_color',
        'working_hours',
        'phone_number',
        'address',
        'text_after_logo',
        'curtain_subcategories',
        'blind_subcategories',
    ];

}
