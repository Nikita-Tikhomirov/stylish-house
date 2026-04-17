<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Review;
use App\Models\HomePage;
use App\Models\IconCard;
use App\Models\VideoReviews;
use App\Models\Faq;
use App\Models\Product;
use App\Models\ProdModel;
use App\Models\Fabric;
use App\Models\ThrouElement;

use App\Services\CartService;



use Illuminate\Support\Facades\Log;

use Illuminate\Http\Request;
use App\Models\WorkExample;

class CategoryController extends Controller
{

    protected $cartService;

    public function show(Request $request, string $slug, $subcategorySlug = null)
    {
        $category = Category::where('slug', $slug)->firstOrFail();
        $subcategory = $subcategorySlug ? Subcategory::where('slug', $subcategorySlug)->where('category_id', $category->id)->firstOrFail() : null;
        $reviews = Review::all();
        $homePageFields = HomePage::firstOrFail();
        $iconCards = IconCard::all();

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

        $subcatsForSlider = Category::where('slug', $slug)
            ->with(['subcategories.products'])
            ->firstOrFail();

        foreach ($subcatsForSlider->subcategories as $subcat) {
            if ($subcat->products->count() === 0 && $subcat->clone_subcategory_id) {
                $cloneSubcat = Subcategory::withCount('products')
                    ->find($subcat->clone_subcategory_id);
                $subcat->products_count = $cloneSubcat->products_count ?? 0;
            } else {
                $subcat->products_count = $subcat->products->count();
            }
        }

        // Загружаем товары для каждой подкатегории для новых секций
        $subcategoriesWithProducts = $subcatsForSlider->subcategories->map(function ($subcat) {
            $products = Product::where('subcategory_id', $subcat->id)
                ->with(['category', 'subcategory'])
                ->limit(8) // Ограничиваем количество товаров для слайдера
                ->get();
            
            return [
                'subcategory' => $subcat,
                'products' => $products
            ];
        });



        $faqs = Faq::where('category_id', $category->id)->whereNull('subcategory_id')->get();
        $videoReviews = VideoReviews::where('category_id', $category->id)
            ->when($subcategory, function ($query) use ($subcategory) {
                return $query->where('subcategory_id', $subcategory->id);
            })
            ->get();

        // Логика для загрузки примеров работ
        if ($subcategory) {
            // Примеры работ для подкатегории
            $workExamples = WorkExample::where('subcategory_id', $subcategory->id)->get();
        } else {
            // Примеры работ для категории, исключая те, у которых есть подкатегория
            $workExamples = WorkExample::where('category_id', $category->id)
                ->whereNull('subcategory_id') // Исключаем работы с подкатегорией
                ->get();
        }

        $filterProduts = Product::where('category_id', $category->id)
            ->leftJoin('prod_model', 'products.model_id', '=', 'prod_model.id')
            ->select('products.*', 'prod_model.title as model_title')
            ->with(['category', 'subcategory'])
            ->paginate(12);

        if ($request->ajax()) {
            return response()->json([
                'filterProduts' => view('front.partials.catproducts', compact('filterProduts'))->render(),
                'pagination' => (string) $filterProduts->withQueryString()->links(),
            ]);
        }



        $models = ProdModel::whereHas('products', function ($query) use ($category) {
            $query->where('category_id', $category->id);
        })->get();

        $filterColors = Product::where('category_id', $category->id)
            ->distinct()
            ->pluck('color');
        $cart = $request->session()->get('cart', []);

        if ($category->calc_prod) {
            // Если calc_prod заполнен
            $firstProduct = Product::leftJoin('prod_model', 'products.model_id', '=', 'prod_model.id')
                ->select('products.*', 'prod_model.title as model_title', 'prod_model.id as model_id')
                ->where('products.id', $category->calc_prod)
                ->first();

            if ($firstProduct) {
                $sameModelProducts = Product::where('model_id', $firstProduct->model_id)
                    ->where('id', '!=', $firstProduct->id) // Исключаем первый товар из выборки
                    ->limit(3) // Ограничиваем до 3, чтобы вместе с первым было 4
                    ->get();

                // Добавляем первый товар в начало коллекции
                $sameModelProducts->prepend($firstProduct);
            } else {
                // Если товар с указанным ID не найден, получаем случайные товары
                $sameModelProducts = Product::inRandomOrder()->limit(4)->get();
            }
        } else {
            // Если calc_prod не заполнен, получаем случайные товары
            $sameModelProducts = Product::inRandomOrder()->limit(4)->get();
        }


        $materials = Product::where('category_id', $category->id)
            ->distinct()
            ->pluck('material')
            ->map(function ($material) {
                $fabric = Fabric::where('name', $material)->first();
                return [
                    'name' => $material,
                    'image' => $fabric ? $fabric->image : null,
                ];
            });
        $headerInfo = ThrouElement::firstOrFail();


        $curtainSubcats = Subcategory::whereIn('id', $headerInfo->curtain_subcategories ?? [])->with('category')->get();
        $blindSubcats = Subcategory::whereIn('id', $headerInfo->blind_subcategories ?? [])->with('category')->get();

        $relatedItems = $category->relatedItems();
        $relatedCategories = $relatedItems['categories'];
        $relatedSubcategories = $relatedItems['subcategories'];

        if ($category->id === 16) {
            return view('front.categoryrolstavni', compact('category', 'reviews', 'homePageFields', 'iconCards', 'categoriesInCatalogMenu', 'categoriesInHeaderMenu', 'subcatsForSlider', 'subcategoriesWithProducts', 'faqs', 'videoReviews', 'workExamples', 'filterProduts', 'models', 'filterColors', 'cart', 'firstProduct', 'sameModelProducts', 'materials', 'headerInfo', 'curtainSubcats', 'blindSubcats', 'relatedCategories', 'relatedSubcategories'));
        } else {
            return view('front.category', compact('category', 'reviews', 'homePageFields', 'iconCards', 'categoriesInCatalogMenu', 'categoriesInHeaderMenu', 'subcatsForSlider', 'subcategoriesWithProducts', 'faqs', 'videoReviews', 'workExamples', 'filterProduts', 'models', 'filterColors', 'cart', 'firstProduct', 'sameModelProducts', 'materials', 'headerInfo', 'curtainSubcats', 'blindSubcats', 'relatedCategories', 'relatedSubcategories'));
        }
        

    }
    public function edit($slug, $subcategorySlug = null)
    {
        $category = Category::where('slug', $slug)->firstOrFail();
        $subcategory = $subcategorySlug ? Subcategory::where('slug', $subcategorySlug)->where('category_id', $category->id)->firstOrFail() : null;

        // Получаем видеообзоры, относящиеся только к текущей категории и подкатегории (если есть)
        $videoReviews = VideoReviews::where('category_id', $category->id)
            ->when($subcategory, function ($query) use ($subcategory) {
                return $query->where('subcategory_id', $subcategory->id);
            })
            ->get();

        // Логика для загрузки примеров работ
        if ($subcategory) {
            // Примеры работ для подкатегории
            $workExamples = WorkExample::where('subcategory_id', $subcategory->id)->get();
        } else {
            // Примеры работ для категории, исключая те, у которых есть подкатегория
            $workExamples = WorkExample::where('category_id', $category->id)
                ->whereNull('subcategory_id') // Исключаем работы с подкатегорией
                ->get();
        }
        $relatedItems = $category->relatedItems();
        // FAQ по категории
        $faqs = Faq::where('category_id', $category->id)->whereNull('subcategory_id')->get();
        $subcategories = Subcategory::all();
        $categories = Category::all();
        $relatedIds = $category->related_items_ids ?? []; // Получаем связанные ID

        return view('admin.catEdit', compact('category', 'videoReviews', 'workExamples', 'faqs', 'subcategory', 'subcategories', 'categories', 'relatedIds'));
    }




    public function create()
    {

        return view('admin.createCat');
    }

    // Метод для сохранения новой категории
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:categories,slug',
            'description' => 'nullable|string',
            'img' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'first_screen_text' => 'required|string|max:255',
            'titleh1' => 'required|string|max:255',
            'subcat_title' => 'string|max:255',
        ]);

        // Создаем новую категорию с данными, кроме изображения
        $category = Category::create([
            'title' => $request->title,
            'slug' => $request->slug,
            'description' => $request->description,
            'first_screen_text' => $request->first_screen_text,
            'titleh1' => $request->titleh1,
            'subcat_title' => $request->subcat_title,
        ]);

        // Проверяем и сохраняем изображение
        if ($request->hasFile('img')) {
            $imagePath = $request->file('img')->store('categories', 'public');
            $category->img = $imagePath;
            $category->save();
        }

        // Редиректим на страницу редактирования только что созданной категории
        return redirect()->route('categories.edit', ['slug' => $category->slug]);
    }





    // Метод обновления категории
    public function update(Request $request, $slug)
    {
        // $request->validate([
        //     'id' => 'required|exists:work_examples,id',
        //     'title' => 'required|string|max:255',
        //     'description' => 'nullable|string',
        //     'category_id' => 'required|exists:categories,id',
        //     'subcategory_id' => 'nullable|exists:subcategories,id',
        // ]);

        // $workExample = WorkExample::findOrFail($request->input('id'));

        // // Убедитесь, что работа принадлежит текущей категории или подкатегории
        // if (
        //     $workExample->category_id != $request->input('category_id') ||
        //     ($request->input('subcategory_id') && $workExample->subcategory_id != $request->input('subcategory_id'))
        // ) {
        //     return response()->json(['error' => 'Вы не можете обновить эту работу.'], 403);
        // }

        // $workExample->title = $request->input('title');
        // $workExample->description = $request->input('description');
        // $workExample->save();

        // return response()->json(['success' => 'Работа успешно обновлена.']);


        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'slug' => 'required|string|max:255|unique:subcategories,slug,' . $slug . ',slug',
            'titleh1' => 'nullable|string|max:255',
            'first_screen_text' => 'nullable|string',
            'img' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,svg|max:2048',
            'subcat_title' => 'string|max:255',
            'faq' => 'nullable|string',
        ]);

        // Находим категорию и подкатегорию
        $category = Category::where('slug', $slug)->firstOrFail();


        // Обновление полей подкатегории
        $category->title = $request->input('title');
        $category->description = $request->input('description');
        $category->slug = $request->input('slug');
        $category->titleh1 = $request->input('titleh1');
        $category->first_screen_text = $request->input('first_screen_text');
        $category->subcat_title = $request->input('subcat_title');
        $category->faq = $request->input('faq');

        // Если загружено изображение, обработать его
        if ($request->hasFile('img')) {
            $imagePath = $request->file('img')->store('categories', 'public');
            $category->img = $imagePath;
        }

        // Сохраняем подкатегорию
        $category->save();

        // Возвращаем JSON-ответ
        return response()->json(['success' => 'Категория успешно обновлена']);
    }



    public function destroy($slug)
    {
        $category = Category::where('slug', $slug)->firstOrFail();
        $category->delete();

        return response()->json(['success' => 'Категория удалена успешно!']);
    }

    public function updateSeoSection(Request $request, $slug)
    {

        $request->validate([
            'seo' => 'required|string',
        ]);

        // Найдите или создайте экземпляр SeoSection
        $category = Category::where('slug', $slug)->firstOrFail();
        $category->seo = $request->input('seo');
        $category->save();


        // Возврат успешного ответа
        return response()->json(['message' => 'Контент успешно обновлен!'], 200);
    }

    public function updateVisibility(Request $request, $slug)
    {
        $category = Category::where('slug', $slug)->firstOrFail();

        $category->show_in_menu = $request->input('show_in_menu');
        $category->show_in_catalog = $request->input('show_in_catalog');
        $category->related_items_ids = $request->input('related_items_ids');

        $category->calc_prod = $request->input('calc_prod');


        $category->save();

        return response()->json(['message' => 'Категория успешно обновлена']);
    }



    public function filterProducts(Request $request, $id)
    {
        $subcategories = $request->input('subcategories', []);
        $models = $request->input('models', []);
        $colors = $request->input('colors', []);
        $materials = $request->input('materials', []);
        $page = $request->input('page', 1);

        $query = Product::with(['category', 'subcategory'])
            ->where('category_id', $id);

        if (!empty($subcategories)) {
            $query->whereIn('subcategory_id', $subcategories);
        }

        if (!empty($models)) {
            $query->whereIn('model_id', $models);
        }

        if (!empty($colors)) {
            $query->whereIn('color', $colors);
        }

        if (!empty($materials)) {
            $query->whereIn('material', $materials);
        }

        $products = $query->paginate(12, ['*'], 'page', $page);

        $encodePath = function (?string $path) {
            if (!$path)
                return null;
            $cleanPath = ltrim($path, '/');
            $dir = dirname($cleanPath);
            $file = basename($cleanPath);
            return asset(($dir !== '.' ? $dir . '/' : '') . rawurlencode($file));
        };

        $productsData = $products->getCollection()->map(function ($product) use ($encodePath) {
            $prodModel = ProdModel::find($product->model_id);
            $prodModelName = $prodModel->title;

            return [
                'id' => $product->id,
                'slug' => $product->slug,
                'h1' => $product->h1,
                'image_path' => $product->image_path,
                'category' => [
                    'slug' => $product->category->slug,
                    'titleh1' => $product->category->titleh1,
                ],
                'subcategory' => [
                    'slug' => $product->subcategory->slug,
                ],
                'price' => $product->price,
                'old_price' => $product->old_price,
                'model' => $prodModelName,
                'modelid' => $product->model_id,
                'cloth' => $product->cloth,
                'discount' => $product->discount,
                'fabric_photo' => $encodePath($product->fabric_photo),
            ];
        });

        return response()->json([
            'products' => $productsData,
            'pagination' => (string) $products->links(),
        ]);
    }





}
