<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PriceRecalcRun extends Model
{
    use HasFactory;

    public const STATUS_RUNNING = 'running';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_STOPPED = 'stopped';
    public const STATUS_DONE = 'done';
    public const STATUS_FAILED = 'failed';

    public const MODE_AUTO = 'auto';
    public const MODE_MANUAL = 'manual';

    protected $fillable = [
        'status',
        'mode',
        'category_id',
        'subcategory_id',
        'model_ids',
        'batch_size',
        'start_id',
        'end_id',
        'current_id',
        'skip_filled',
        'overwrite_existing',
        'last_product_id',
        'processed',
        'updated',
        'skipped',
        'total_candidates',
        'progress_percent',
        'eta_seconds',
        'stop_reason',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'model_ids' => 'array',
        'skip_filled' => 'boolean',
        'overwrite_existing' => 'boolean',
        'progress_percent' => 'decimal:2',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(PriceRecalcRunItem::class, 'run_id');
    }

    public function isActive(): bool
    {
        return in_array($this->status, [self::STATUS_RUNNING, self::STATUS_PAUSED], true);
    }
}
