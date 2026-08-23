<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Subcategory;
use App\Services\NavigationService;
use Illuminate\Database\Seeder;

class HeaderNavigationSeeder extends Seeder
{
    public function run(): void
    {
        $menu = NavigationMenu::firstOrCreate(
            ['key' => 'header'],
            ['name' => 'Главное меню']
        );

        if ($menu->items()->exists()) {
            $this->syncCurtainModelSections($menu);
            app(NavigationService::class)->forgetHeader();
            return;
        }

        foreach ($this->structure() as $position => $definition) {
            $category = Category::where('slug', $definition['slug'])->first();
            if (! $category) {
                continue;
            }

            $tab = $this->createItem($menu, [
                'node_type' => 'tab',
                'label' => $definition['label'],
                'source_type' => 'category',
                'source_id' => $category->id,
                'position' => $position,
            ]);

            $this->createItem($menu, [
                'node_type' => 'link',
                'placement' => 'quick',
                'label' => $definition['label'],
                'source_type' => 'category',
                'source_id' => $category->id,
                'position' => $position,
            ]);

            foreach ($definition['sections'] as $sectionPosition => $sectionDefinition) {
                $links = Subcategory::query()
                    ->where('category_id', $category->id)
                    ->whereIn('slug', $sectionDefinition['slugs'])
                    ->get()
                    ->keyBy('slug');

                if ($links->isEmpty()) {
                    continue;
                }

                $section = $this->createItem($menu, [
                    'parent_id' => $tab->id,
                    'node_type' => 'section',
                    'label' => $sectionDefinition['label'],
                    'position' => $sectionPosition,
                ]);

                foreach ($sectionDefinition['slugs'] as $linkPosition => $slug) {
                    $subcategory = $links->get($slug);
                    if (! $subcategory) {
                        continue;
                    }

                    $this->createItem($menu, [
                        'parent_id' => $section->id,
                        'node_type' => 'link',
                        'label' => $subcategory->menu_title ?: $subcategory->titleh1 ?: $subcategory->title,
                        'source_type' => 'subcategory',
                        'source_id' => $subcategory->id,
                        'position' => $linkPosition,
                    ]);
                }
            }
        }

        foreach ($this->utilityLinks() as $position => [$label, $url]) {
            $this->createItem($menu, [
                'node_type' => 'link',
                'placement' => 'utility',
                'label' => $label,
                'source_type' => 'custom',
                'url' => $url,
                'position' => $position,
            ]);
        }

        $this->syncCurtainModelSections($menu);
        app(NavigationService::class)->forgetHeader();
    }

    private function syncCurtainModelSections(NavigationMenu $menu): void
    {
        $category = Category::query()->where('slug', 'story')->first();
        if (! $category) {
            return;
        }

        $tab = $menu->items()
            ->where('node_type', 'tab')
            ->where('source_type', 'category')
            ->where('source_id', $category->id)
            ->first();
        if (! $tab) {
            return;
        }

        $definitions = $this->curtainModelSections();
        $labels = array_column($definitions, 'label');
        $otherSections = $menu->items()
            ->where('parent_id', $tab->id)
            ->where('node_type', 'section')
            ->whereNotIn('label', $labels)
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        foreach ($otherSections as $position => $section) {
            $section->update(['position' => $position + count($definitions)]);
        }

        foreach ($definitions as $sectionPosition => $definition) {
            $section = NavigationItem::updateOrCreate([
                'navigation_menu_id' => $menu->id,
                'parent_id' => $tab->id,
                'node_type' => 'section',
                'label' => $definition['label'],
            ], [
                'placement' => 'mega',
                'position' => $sectionPosition,
                'is_active' => true,
            ]);

            foreach ($definition['links'] as $linkPosition => [$label, $url]) {
                NavigationItem::updateOrCreate([
                    'navigation_menu_id' => $menu->id,
                    'parent_id' => $section->id,
                    'node_type' => 'link',
                    'label' => $label,
                ], [
                    'placement' => 'mega',
                    'source_type' => 'custom',
                    'source_id' => null,
                    'url' => $url,
                    'position' => $linkPosition,
                    'is_active' => true,
                ]);
            }

            $section->children()
                ->where('node_type', 'link')
                ->whereNotIn('label', array_column($definition['links'], 0))
                ->delete();
        }
    }

    private function curtainModelSections(): array
    {
        return [
            [
                'label' => 'На пластиковые окна',
                'links' => [
                    ['Мини', '/story/rulonnye-shtory-na-plastikovye-okna/?model=43'],
                    ['УНИ-1', '/story/rulonnye-shtory-na-plastikovye-okna/?model=45'],
                    ['УНИ-2', '/story/rulonnye-shtory-na-plastikovye-okna/?model=46'],
                    ['Комбо Мини Нью', '/story/kombo-den-noch-na-plastikovye-okna/?model=49'],
                    ['Комбо УНИ-1 белый', '/story/kombo-den-noch-na-plastikovye-okna/?model=50'],
                    ['Комбо УНИ-2 белый', '/story/kombo-den-noch-na-plastikovye-okna/?model=51'],
                    ['Комбо УНИ-2 ламинированный', '/story/kombo-den-noch-na-plastikovye-okna/?model=52'],
                ],
            ],
            [
                'label' => 'Свободновисящие',
                'links' => [
                    ['Все рулонные шторы', '/story/rulstori/'],
                    ['Стандарт', '/story/rulstori/?model=33'],
                    ['Гранд', '/story/rulstori/?model=35'],
                    ['Спринг', '/story/rulstori/?model=36'],
                    ['Кватро классик', '/story/rulstori/?model=37'],
                    ['Кватро люкс', '/story/rulstori/?model=38'],
                    ['Классик премиум', '/story/rulstori/?model=39'],
                    ['Дабл классик', '/story/rulstori/?model=40'],
                    ['Люкс премиум', '/story/rulstori/?model=41'],
                    ['Дабл люкс', '/story/rulstori/?model=42'],
                ],
            ],
        ];
    }

    private function createItem(NavigationMenu $menu, array $attributes): NavigationItem
    {
        return NavigationItem::create(array_merge([
            'navigation_menu_id' => $menu->id,
            'placement' => 'mega',
            'position' => 0,
            'is_active' => true,
        ], $attributes));
    }

    private function utilityLinks(): array
    {
        return [
            ['Рассчитать стоимость', '/shop-pages/rasschitat/'],
            ['Портфолио', '/shop-pages/portfolio/'],
            ['Оплата и доставка', '/shop-pages/oplata-i-dostavka/'],
            ['Контакты', '/shop-pages/kontakty/'],
        ];
    }

    private function structure(): array
    {
        return [
            [
                'slug' => 'jaluzi',
                'label' => 'Жалюзи',
                'sections' => [
                    ['label' => 'Популярные', 'slugs' => ['gorizontalnye-zhalyuzi', 'vertikalnye-zhalyuzi', 'kassetnye-zhalyuzi-na-plastikovye-okna']],
                    ['label' => 'По материалу', 'slugs' => ['zhalyuzi‑tkanevye', 'zhalyuzi-alyuminievye', 'zhalyuzi-plastikovye', 'zhalyuzi-derevyannye', 'zhalyuzi-bambukovye']],
                    ['label' => 'Горизонтальные', 'slugs' => ['zhalyuzi-bambukovye-gorizontalnye', 'zhalyuzi-gorizontalnye-alyuminievye', 'zhalyuzi-gorizontalnye-derevyannye', 'zhalyuzi-metallicheskie-gorizontalnye']],
                    ['label' => 'Вертикальные', 'slugs' => ['gz-tkan', 'gz-plastik', 'vertikalnye-alyuminievye-zhalyuzi', 'zhalyuzi-vertikalnye-ofisnye', 'zhalyuzi-vertikalnye-na-okna']],
                ],
            ],
            [
                'slug' => 'story',
                'label' => 'Шторы',
                'sections' => [
                    ['label' => 'Популярные', 'slugs' => ['rulstori', 'rulonnye-shtory-na-plastikovye-okna', 'kombo-den-noch-na-plastikovye-okna', 'kombo-den-noch-svobodnovisyashchie', 'rimskieshtory', 'shtoryplisse']],
                    ['label' => 'По назначению', 'slugs' => ['rulonnye-shtory-v-spalnyu', 'rulonnye-shtory-v-ofis', 'rulonnye-shtory-na-kuhnyu', 'rulonnye-shtory-v-detskuyu', 'wanna', 'rszgosti']],
                    ['label' => 'По свойствам', 'slugs' => ['rulonnye-shtory-blekaut', 'rulonnye-shtory-den-noch', 'rulonnye-shtory-zebra-na-plastikovye-okna', 'sv-sztory', 'kasset-rul', 'rulonnye-shtory-zakrytogo', 'rul-bez-swerl', 'rs-oknoprojem', 'rs-poluprozracz', 'rs-nedorogo', 'rszpremium']],
                    ['label' => 'По цветам', 'slugs' => ['pszbelyje', 'rszseryje', 'rzsbegevyje', 'rszschokolad', 'rszgolub', 'rzroza', 'rszsini', 'rszterakot', 'rszkrasnyj', 'rszolivki', 'rszkoriczniewye', 'rszkaram', 'rszkrem', 'rszsiren', 'rszzelen', 'rszberiuza', 'tczjernyje', 'rszpesok', 'rszgolt']],
                ],
            ],
            [
                'slug' => 'rolstavni',
                'label' => 'Рольставни и ворота',
                'sections' => [
                    ['label' => 'Рольставни', 'slugs' => ['santehnicheskie-rolleti', 'rollety-dlya-proyoma', 'rolstavni-na-okna']],
                    ['label' => 'Ворота', 'slugs' => ['sekcionnye-vorota', 'promyshlennye-vorota', 'rollety-dlya-vorot']],
                ],
            ],
        ];
    }
}
