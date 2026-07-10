<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Page;
use App\Services\CartService;
use App\Models\Product;
use App\Models\ProdModel;
use App\Models\Category;
use App\Models\ThrouElement;
use App\Models\HomePage;
use App\Models\Subcategory;
use App\Models\VideoReviews;
use App\Models\WorkExample;

class PageController extends Controller
{

    public function index(Request $request, string $slug, )
    {
        $page = Page::where('slug', $slug)->firstOrFail();

        // Получаем категории, подкатегории и товары, где show_in_catalog = true
        $categoriesInCatalogMenu = Category::where('show_in_catalog', true)
            ->with([
                'subcategories' => function ($query) {
                    $query->where('show_in_catalog', true)
                        ->with([
                            'products' => function ($query) {
                                $query->where('show_in_catalog', true);
                            }
                        ]);
                }
            ])
            ->get();

        $categoriesInHeaderMenu = Category::where('show_in_menu', true)
            ->with([
                'subcategories' => function ($query) {
                    $query->where('show_in_menu', true)
                        ->with([
                            'products' => function ($query) {
                                $query->where('show_in_menu', true);
                            }
                        ]);
                }
            ])
            ->get();
        $cart = $request->session()->get('cart', []);
        $headerInfo = ThrouElement::firstOrFail();
        $homePageFields = HomePage::firstOrFail();
        $curtainSubcats = Subcategory::whereIn('id', $headerInfo->curtain_subcategories ?? [])->with('category')->get();
        $blindSubcats = Subcategory::whereIn('id', $headerInfo->blind_subcategories ?? [])->with('category')->get();
        $calculatorCategories = Category::whereIn('slug', ['jaluzi', 'story', 'rolstavni'])
            ->with(['subcategories' => function ($query) {
                $query->where('show_in_catalog', true)->orderBy('titleh1');
            }])
            ->orderBy('id')
            ->get();
        $portfolioWorkExamples = WorkExample::where(function ($query) {
                $query->whereNull('title')
                    ->orWhere(function ($query) {
                        $query->where('title', 'not like', '%Роллеты для ворот - пример%')
                            ->where('title', 'not like', '%Секционные ворота - пример%')
                            ->where('title', 'not like', '%Промышленные ворота - пример%');
                    });
            })
            ->latest()
            ->get();
        $portfolioWorkExampleGroups = $portfolioWorkExamples
            ->groupBy(fn (WorkExample $workExample) => $this->portfolioGroupName($workExample))
            ->sortKeysUsing(function ($first, $second) {
                if ($first === 'Примеры работ') {
                    return 1;
                }
                if ($second === 'Примеры работ') {
                    return -1;
                }

                return strnatcasecmp($first, $second);
            });
        $portfolioVideos = VideoReviews::latest()->get();

        return view('front.pages', compact(
            'page',
            'categoriesInCatalogMenu',
            'categoriesInHeaderMenu',
            'cart',
            'headerInfo',
            'homePageFields',
            'curtainSubcats',
            'blindSubcats',
            'calculatorCategories',
            'portfolioWorkExamples',
            'portfolioWorkExampleGroups',
            'portfolioVideos'
        ));
    }

    private function portfolioGroupName(WorkExample $workExample): string
    {
        $description = trim((string) $workExample->description);

        if (preg_match('/^portfolio_group:(.+)$/u', $description, $matches)) {
            return trim($matches[1]) ?: 'Примеры работ';
        }

        return 'Примеры работ';
    }

    public function show()
    {
        $pages = Page::all();
        return view('admin.pages', compact('pages'));
    }



    // Форма создания страницы
    public function create()
    {
        return view('admin.pagescreate');
    }

    // Сохранение новой страницы
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'h1' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:pages',
            'content' => 'nullable|string',
        ]);

        Page::create($request->all());
        return redirect('/allpages')->with('success', 'Страница создана');
    }

    // Форма редактирования
    public function edit(Page $page)
    {
        return view('admin.pagesedit', compact('page'));
    }

    // Обновление страницы
    public function update(Request $request, Page $page)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'h1' => 'required|string|max:255',
            'content' => 'nullable|string',
        ]);

        $page->update($request->all());
        return redirect('/allpages')->with('success', 'Страница обновлена');
    }

    // Удаление страницы
    public function destroy(Page $page)
    {
        $page->delete();
        return redirect()->route('pages.show')->with('success', 'Страница удалена');
    }

    public function uploadImage(Request $request)
    {
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('pages', $filename, 'public'); // Сохраняем файл в 'public/pages'
            return response()->json(['url' => asset('storage/pages/' . $filename)]); // Генерируем правильный URL
        }

        return response()->json(['error' => 'Файл не загружен'], 400);
    }



}
