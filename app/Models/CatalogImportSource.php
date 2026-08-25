<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CatalogImportSource extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_RUNNING = 'running';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_ERROR = 'error';

    public const REVIEW_NEEDS_REVIEW = 'needs_review';

    public const REVIEW_APPROVED = 'approved';

    public const REVIEW_REJECTED = 'rejected';

    protected $fillable = [
        'catalog_import_run_id',
        'label',
        'source_url',
        'target_slug',
        'enabled',
        'status',
        'sort_order',
        'pages_count',
        'items_count',
        'next_page_url',
        'rewritten_title',
        'rewritten_h1',
        'rewritten_intro',
        'rewritten_description',
        'rewritten_seo',
        'review_status',
        'review_notes',
        'warnings',
        'error',
        'published_subcategory_id',
        'created_subcategory',
        'publication_snapshot',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'sort_order' => 'integer',
        'pages_count' => 'integer',
        'items_count' => 'integer',
        'warnings' => 'array',
        'created_subcategory' => 'boolean',
        'publication_snapshot' => 'array',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(CatalogImportRun::class, 'catalog_import_run_id');
    }

    public function items(): BelongsToMany
    {
        return $this->belongsToMany(
            CatalogImportItem::class,
            'catalog_import_item_source',
            'import_source_id',
            'import_item_id'
        )->withTimestamps();
    }

    public function publishedSubcategory(): BelongsTo
    {
        return $this->belongsTo(Subcategory::class, 'published_subcategory_id');
    }
}
