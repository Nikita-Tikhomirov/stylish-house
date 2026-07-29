<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateNavigationMenuRequest;
use App\Models\Category;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Models\Subcategory;
use App\Services\NavigationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class NavigationMenuController extends Controller
{
    public function edit(): View
    {
        $menu = NavigationMenu::query()->firstOrCreate(
            ['key' => 'header'],
            ['name' => 'Главное меню']
        );
        $items = $menu->items()->orderBy('position')->orderBy('id')->get();
        $children = $items->groupBy(fn (NavigationItem $item) => $item->parent_id ?? 0);

        return view('admin.navigation-menu', [
            'editorItems' => $items
                ->whereNull('parent_id')
                ->map(fn (NavigationItem $item) => $this->editorItem($item, $children))
                ->values()
                ->all(),
            'navigationSources' => [
                'category' => Category::query()->orderBy('title')->get(['id', 'title']),
                'subcategory' => Subcategory::query()
                    ->with('category:id,title')
                    ->orderBy('title')
                    ->get(['id', 'category_id', 'title'])
                    ->map(fn (Subcategory $item) => [
                        'id' => $item->id,
                        'title' => trim(($item->category?->title ? $item->category->title.' / ' : '').$item->title),
                    ])
                    ->values(),
                'page' => Page::query()->orderBy('title')->get(['id', 'title']),
            ],
        ]);
    }

    public function update(UpdateNavigationMenuRequest $request, NavigationService $navigation): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $menu = NavigationMenu::query()->firstOrCreate(
                ['key' => 'header'],
                ['name' => 'Главное меню']
            );

            $menu->items()->delete();

            foreach ($request->validated('items') as $position => $item) {
                $this->storeItem($menu, $item, $position);
            }
        });

        $navigation->forgetHeader();

        return redirect()
            ->route('admin.navigation.edit')
            ->with('status', 'Структура меню сохранена.');
    }

    private function storeItem(
        NavigationMenu $menu,
        array $data,
        int $position,
        ?NavigationItem $parent = null
    ): void {
        $item = $menu->items()->create([
            'parent_id' => $parent?->id,
            'node_type' => $data['node_type'],
            'placement' => $data['placement'] ?? $parent?->placement ?? 'mega',
            'label' => trim($data['label']),
            'source_type' => $data['source_type'] ?? null,
            'source_id' => $data['source_id'] ?? null,
            'url' => $data['url'] ?? null,
            'position' => $position,
            'is_active' => filter_var($data['is_active'] ?? true, FILTER_VALIDATE_BOOL),
        ]);

        foreach (($data['children'] ?? []) as $childPosition => $child) {
            $this->storeItem($menu, $child, $childPosition, $item);
        }
    }

    private function editorItem(NavigationItem $item, Collection $children): array
    {
        return [
            'id' => $item->id,
            'node_type' => $item->node_type,
            'placement' => $item->placement,
            'label' => $item->label,
            'source_type' => $item->source_type,
            'source_id' => $item->source_id,
            'url' => $item->url,
            'is_active' => $item->is_active,
            'children' => $children->get($item->id, collect())
                ->map(fn (NavigationItem $child) => $this->editorItem($child, $children))
                ->values()
                ->all(),
        ];
    }
}
