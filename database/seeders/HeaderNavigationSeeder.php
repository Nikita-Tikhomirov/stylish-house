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

        app(NavigationService::class)->forgetHeader();
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
            ['Рассчитать стоимость', '/shop-pages/rasschitat'],
            ['Портфолио', '/shop-pages/portfolio'],
            ['Оплата и доставка', '/shop-pages/oplata-i-dostavka'],
            ['Контакты', '/shop-pages/kontakty'],
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
