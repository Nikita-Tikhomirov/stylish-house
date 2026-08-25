<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CatalogImportRun extends Model
{
    use HasFactory;

    public const STATUS_STAGED = 'staged';

    public const STATUS_REVIEWING = 'reviewing';

    public const STATUS_PUBLISHING = 'publishing';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_ROLLED_BACK = 'rolled_back';

    public const STATUS_ERROR = 'error';

    protected $fillable = [
        'provider',
        'external_run_id',
        'status',
        'config',
        'source_count',
        'page_count',
        'unique_product_count',
        'image_count',
        'membership_count',
        'duplicate_count',
        'error_count',
        'error',
        'started_at',
        'completed_at',
        'published_at',
        'backup_created_at',
        'backup_path',
        'backup_sha256',
    ];

    protected $casts = [
        'config' => 'array',
        'source_count' => 'integer',
        'page_count' => 'integer',
        'unique_product_count' => 'integer',
        'image_count' => 'integer',
        'membership_count' => 'integer',
        'duplicate_count' => 'integer',
        'error_count' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'published_at' => 'datetime',
        'backup_created_at' => 'datetime',
    ];

    public function sources(): HasMany
    {
        return $this->hasMany(CatalogImportSource::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CatalogImportItem::class);
    }
}
