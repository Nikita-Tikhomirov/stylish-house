<?php

namespace App\Models;

use App\Support\CanonicalUrl;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NavigationItem extends Model
{
    protected $fillable = [
        'navigation_menu_id',
        'parent_id',
        'node_type',
        'placement',
        'label',
        'source_type',
        'source_id',
        'url',
        'position',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'position' => 'integer',
    ];

    public function menu(): BelongsTo
    {
        return $this->belongsTo(NavigationMenu::class, 'navigation_menu_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('position')->orderBy('id');
    }

    public function categorySource(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'source_id');
    }

    public function subcategorySource(): BelongsTo
    {
        return $this->belongsTo(Subcategory::class, 'source_id');
    }

    public function pageSource(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'source_id');
    }

    public function resolvedUrl(): string
    {
        return match ($this->source_type) {
            'category' => $this->categoryUrl(),
            'subcategory' => $this->subcategoryUrl(),
            'page' => $this->pageUrl(),
            'custom' => CanonicalUrl::to($this->url ?: '#'),
            default => CanonicalUrl::to($this->url ?: '#'),
        };
    }

    private function categoryUrl(): string
    {
        $category = $this->categorySource;

        return $category ? CanonicalUrl::route('category.show', ['slug' => $category->slug], false) : '#';
    }

    private function subcategoryUrl(): string
    {
        $subcategory = $this->subcategorySource;
        $category = $subcategory?->category;

        if (! $subcategory || ! $category) {
            return '#';
        }

        return CanonicalUrl::route('subcategory.show', [
            'category_slug' => $category->slug,
            'subcategory_slug' => $subcategory->slug,
        ], false);
    }

    private function pageUrl(): string
    {
        $page = $this->pageSource;

        return $page ? CanonicalUrl::route('pages.index', ['slug' => $page->slug], false) : '#';
    }
}
