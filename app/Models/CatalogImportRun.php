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
        'backup_manifest_path',
        'backup_manifest_sha256',
        'backup_raw_sha256',
        'backup_raw_size',
        'backup_gzip_size',
        'warnings_acknowledged_at',
        'warnings_acknowledged_by',
        'warnings_acknowledged_sha256',
        'sitemap_generated_at',
        'sitemap_error',
        'publication_error',
        'publication_journal',
        'rolled_back_at',
        'rollback_error',
        'rollback_journal',
        'rollback_backup_created_at',
        'rollback_backup_path',
        'rollback_backup_sha256',
        'rollback_backup_manifest_path',
        'rollback_backup_manifest_sha256',
        'rollback_backup_raw_sha256',
        'rollback_backup_raw_size',
        'rollback_backup_gzip_size',
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
        'backup_raw_size' => 'integer',
        'backup_gzip_size' => 'integer',
        'warnings_acknowledged_at' => 'datetime',
        'sitemap_generated_at' => 'datetime',
        'publication_journal' => 'array',
        'rolled_back_at' => 'datetime',
        'rollback_journal' => 'array',
        'rollback_backup_created_at' => 'datetime',
        'rollback_backup_raw_size' => 'integer',
        'rollback_backup_gzip_size' => 'integer',
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
