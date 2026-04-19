<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PriceRecalcRun extends Model
{
    use HasFactory;

    public const STATUS_RUNNING = 'running';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_DONE = 'done';

    protected $fillable = [
        'status',
        'category_id',
        'subcategory_id',
        'model_ids',
        'batch_size',
        'last_product_id',
        'processed',
        'updated',
        'skipped',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'model_ids' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];
}
