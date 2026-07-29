<?php

namespace App\Services;

use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class NavigationService
{
    public const HEADER_CACHE_KEY = 'navigation:header-catalog:v1';

    public function header(): array
    {
        return Cache::rememberForever(self::HEADER_CACHE_KEY, fn () => $this->buildHeader());
    }

    public function forgetHeader(): void
    {
        Cache::forget(self::HEADER_CACHE_KEY);
    }

    private function buildHeader(): array
    {
        $menu = NavigationMenu::query()->where('key', 'header')->first();

        if (! $menu) {
            return $this->emptyPayload();
        }

        $items = NavigationItem::query()
            ->where('navigation_menu_id', $menu->id)
            ->where('is_active', true)
            ->with(['categorySource', 'subcategorySource.category', 'pageSource'])
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        $children = $items->groupBy(fn (NavigationItem $item) => $item->parent_id ?? 0);

        return [
            'tabs' => $items
                ->where('placement', 'mega')
                ->where('node_type', 'tab')
                ->whereNull('parent_id')
                ->map(fn (NavigationItem $tab) => $this->tabPayload($tab, $children))
                ->values()
                ->all(),
            'quickLinks' => $this->rootLinkPayloads($items, 'quick'),
            'utilityLinks' => $this->rootLinkPayloads($items, 'utility'),
        ];
    }

    private function tabPayload(NavigationItem $tab, Collection $children): array
    {
        return [
            'id' => $tab->id,
            'label' => $tab->label,
            'url' => $tab->resolvedUrl(),
            'sections' => $children->get($tab->id, collect())
                ->where('node_type', 'section')
                ->map(fn (NavigationItem $section) => [
                    'id' => $section->id,
                    'label' => $section->label,
                    'links' => $children->get($section->id, collect())
                        ->where('node_type', 'link')
                        ->map(fn (NavigationItem $link) => $this->linkPayload($link))
                        ->values()
                        ->all(),
                ])
                ->values()
                ->all(),
        ];
    }

    private function rootLinkPayloads(Collection $items, string $placement): array
    {
        return $items
            ->where('placement', $placement)
            ->where('node_type', 'link')
            ->whereNull('parent_id')
            ->map(fn (NavigationItem $item) => $this->linkPayload($item))
            ->values()
            ->all();
    }

    private function linkPayload(NavigationItem $item): array
    {
        return [
            'id' => $item->id,
            'label' => $item->label,
            'url' => $item->resolvedUrl(),
        ];
    }

    private function emptyPayload(): array
    {
        return [
            'tabs' => [],
            'quickLinks' => [],
            'utilityLinks' => [],
        ];
    }
}
