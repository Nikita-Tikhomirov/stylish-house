<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CatalogAttribute extends Model
{
    use HasFactory;

    public const TYPE_SELECT = 'select';

    public const TYPE_NUMBER = 'number';

    protected $fillable = [
        'code',
        'label',
        'type',
        'unit',
        'sort_order',
        'is_public',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_public' => 'boolean',
    ];

    public function values(): HasMany
    {
        return $this->hasMany(CatalogAttributeValue::class);
    }
}
