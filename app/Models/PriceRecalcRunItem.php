<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PriceRecalcRunItem extends Model
{
    use HasFactory;

    public const STATUS_UPDATED = 'updated';
    public const STATUS_SKIPPED = 'skipped';
    public const STATUS_ERROR = 'error';

    protected $fillable = [
        'run_id',
        'product_id',
        'status',
        'old_min_price',
        'new_min_price',
        'error_code',
        'error_message',
        'processed_at',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
    ];

    public function run()
    {
        return $this->belongsTo(PriceRecalcRun::class, 'run_id');
    }
}
