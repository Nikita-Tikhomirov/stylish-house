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
use App\Support\PreviewCardData;



use Illuminate\Support\Facades\Log;

use Illuminate\Http\Request;
use App\Models\WorkExample;
use App\Models\SubcategoryInstallationType;
use App\Models\RollerShutterSystem;
use Illuminate\Support\Facades\Storage;

class CategoryController extends Controller
{

    protected $cartService;

    protected function serializePreviewProduct(Product $product): array
    {
        return PreviewCardData::fromProduct($product);
    }

    public function show(Request $request, string $slug, $subcategorySlug = null)
    {
        $category = Category::where('slug', $slug)->firstOrFail();
        $subcategory = $subcategorySlug ? Subcategory::where('slug', $subcategorySlug)->where('category_id', $category->id)->firstOrFail() : null;
        $reviews = Review::all();
        $homePageFields = HomePage::firstOrFail();
        $iconCards = IconCard::all();

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
            ->where(function ($query) {
                $query->whereNull('description')
                    ->orWhere('description', '!=', 'home_video');
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

        $maxFilterPrice = (int) Product::where('category_id', $category->id)
            ->whereNotNull('min_price')
            ->where('min_price', '>', 0)
            ->max('min_price');

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

        $installationTypes = SubcategoryInstallationType::where('category_id', $category->id)
            ->orderBy('sort_order')
            ->get();

        $rollerShutterSystems = RollerShutterSystem::where('category_id', $category->id)
            ->orderBy('sort_order')
            ->get();

        if ($category->id === 16) {
            return view('front.categoryrolstavni', compact('category', 'reviews', 'homePageFields', 'iconCards', 'subcatsForSlider', 'subcategoriesWithProducts', 'faqs', 'videoReviews', 'workExamples', 'filterProduts', 'models', 'filterColors', 'cart', 'firstProduct', 'sameModelProducts', 'materials', 'headerInfo', 'curtainSubcats', 'blindSubcats', 'relatedCategories', 'relatedSubcategories', 'maxFilterPrice', 'installationTypes', 'rollerShutterSystems'));
        } else {
            return view('front.category', compact('category', 'reviews', 'homePageFields', 'iconCards', 'subcatsForSlider', 'subcategoriesWithProducts', 'faqs', 'videoReviews', 'workExamples', 'filterProduts', 'models', 'filterColors', 'cart', 'firstProduct', 'sameModelProducts', 'materials', 'headerInfo', 'curtainSubcats', 'blindSubcats', 'relatedCategories', 'relatedSubcategories', 'maxFilterPrice'));
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
        $installationTypes = SubcategoryInstallationType::where('category_id', $category->id)
            ->orderBy('sort_order')
            ->get();

        $rollerShutterSystems = RollerShutterSystem::where('category_id', $category->id)
            ->orderBy('sort_order')
            ->get();

        return view('admin.catEdit', compact('category', 'videoReviews', 'workExamples', 'faqs', 'subcategory', 'subcategories', 'categories', 'relatedIds', 'installationTypes', 'rollerShutterSystems'));
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
            'img' => 'nullable',
            'first_screen_text' => 'required|string|max:255',
            'titleh1' => 'required|string|max:255',
            'subcat_title' => 'nullable|string|max:255',
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
        // Находим категорию до валидации, чтобы использовать её id в unique-правиле
        $category = Category::where('slug', $slug)->firstOrFail();

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'slug' => 'required|string|max:255|unique:categories,slug,' . $category->id,
            'titleh1' => 'nullable|string|max:255',
            'first_screen_text' => 'nullable|string',
            'img' => 'nullable',
            'subcat_title' => 'nullable|string|max:255',
            'faq' => 'nullable|string',
        ]);


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
            $file = $request->file('img');
            Log::info('Uploading image', [
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime' => $file->getMimeType(),
            ]);
            $imagePath = $file->store('categories', 'public');
            $category->img = $imagePath;
            Log::info('Image stored', ['path' => $imagePath]);
        } else {
            Log::warning('No image file in request', [
                'has_img' => $request->has('img'),
                'all_files' => $request->allFiles(),
            ]);
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
        $priceFilter = $this->normalizePriceFilter($request);

        $query = Product::with(['category', 'subcategory', 'model'])
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

        $this->applyMinPriceFilter($query, $priceFilter);

        $products = $query->paginate(12, ['*'], 'page', $page);

        $productsData = $products->getCollection()->map(fn (Product $product) => $this->serializePreviewProduct($product));

        return response()->json([
            'products' => $productsData,
            'pagination' => (string) $products->links(),
        ]);
    }

    /**
     * @return array{active: bool, min: int|null, max: int|null}
     */
    protected function normalizePriceFilter(Request $request): array
    {
        $active = filter_var($request->input('price_filter_active', false), FILTER_VALIDATE_BOOLEAN);
        $minPrice = $request->input('min_price');
        $maxPrice = $request->input('max_price');

        $minPrice = is_numeric($minPrice) ? max(0, (int) $minPrice) : null;
        $maxPrice = is_numeric($maxPrice) ? max(0, (int) $maxPrice) : null;

        if ($minPrice !== null && $maxPrice !== null && $minPrice > $maxPrice) {
            [$minPrice, $maxPrice] = [$maxPrice, $minPrice];
        }

        return [
            'active' => $active,
            'min' => $minPrice,
            'max' => $maxPrice,
        ];
    }

    /**
     * Products without min_price stay visible until the user touches the price filter.
     *
     * @param array{active: bool, min: int|null, max: int|null} $priceFilter
     */
    protected function applyMinPriceFilter($query, array $priceFilter): void
    {
        if (!$priceFilter['active']) {
            return;
        }

        $query->whereNotNull('min_price')
            ->where('min_price', '>', 0);

        if ($priceFilter['min'] !== null) {
            $query->where('min_price', '>=', $priceFilter['min']);
        }

        if ($priceFilter['max'] !== null) {
            $query->where('min_price', '<=', $priceFilter['max']);
        }
    }

    // ============================================================
    // CRUD: Типы установки (installation types) для категорий
    // ============================================================

    public function installationTypes($slug)
    {
        $category = Category::where('slug', $slug)->firstOrFail();
        $types = SubcategoryInstallationType::where('category_id', $category->id)
            ->orderBy('sort_order')
            ->get();

        return response()->json(['types' => $types]);
    }

    public function storeInstallationType(Request $request, $slug)
    {
        $category = Category::where('slug', $slug)->firstOrFail();

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:20480',
            'detail_image' => 'nullable|image|max:20480',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $data = [
            'category_id' => $category->id,
            'title' => $request->title,
            'description' => $request->description,
            'sort_order' => (int) ($request->sort_order ?? 0),
        ];

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('installation_types', 'public');
        }
        if ($request->hasFile('detail_image')) {
            $data['detail_image'] = $request->file('detail_image')->store('installation_types', 'public');
        }

        $type = SubcategoryInstallationType::create($data);

        return response()->json(['success' => true, 'type' => $type]);
    }

    public function updateInstallationType(Request $request, $slug, $id)
    {
        $category = Category::where('slug', $slug)->firstOrFail();
        $type = SubcategoryInstallationType::where('id', $id)
            ->where('category_id', $category->id)
            ->firstOrFail();

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:20480',
            'detail_image' => 'nullable|image|max:20480',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $type->title = $request->title;
        $type->description = $request->description;
        $type->sort_order = (int) ($request->sort_order ?? 0);

        if ($request->hasFile('image')) {
            if ($type->image) {
                Storage::disk('public')->delete($type->image);
            }
            $type->image = $request->file('image')->store('installation_types', 'public');
        }
        if ($request->hasFile('detail_image')) {
            if ($type->detail_image) {
                Storage::disk('public')->delete($type->detail_image);
            }
            $type->detail_image = $request->file('detail_image')->store('installation_types', 'public');
        }

        $type->save();

        return response()->json(['success' => true, 'type' => $type]);
    }

    public function destroyInstallationType($slug, $id)
    {
        $category = Category::where('slug', $slug)->firstOrFail();
        $type = SubcategoryInstallationType::where('id', $id)
            ->where('category_id', $category->id)
            ->firstOrFail();

        if ($type->image) {
            Storage::disk('public')->delete($type->image);
        }
        if ($type->detail_image) {
            Storage::disk('public')->delete($type->detail_image);
        }
        $type->delete();

        return response()->json(['success' => true]);
    }

    // ============================================================
    // CRUD: Системы управления рольставнями (roller shutter systems)
    // ============================================================

    public function storeRollerShutterSystem(Request $request, $slug)
    {
        $category = Category::where('slug', $slug)->firstOrFail();

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'components' => 'nullable|string',
            'image' => 'nullable|image|max:20480',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $data = [
            'category_id' => $category->id,
            'title' => $request->title,
            'description' => $request->description,
            'components' => $request->components,
            'sort_order' => (int) ($request->sort_order ?? 0),
        ];

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('roller_shutter_systems', 'public');
        }

        $system = RollerShutterSystem::create($data);

        return response()->json(['success' => true, 'system' => $system]);
    }

    public function updateRollerShutterSystem(Request $request, $slug, $id)
    {
        $category = Category::where('slug', $slug)->firstOrFail();
        $system = RollerShutterSystem::where('id', $id)
            ->where('category_id', $category->id)
            ->firstOrFail();

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'components' => 'nullable|string',
            'image' => 'nullable|image|max:20480',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $system->title = $request->title;
        $system->description = $request->description;
        $system->components = $request->components;
        $system->sort_order = (int) ($request->sort_order ?? 0);

        if ($request->hasFile('image')) {
            if ($system->image) {
                Storage::disk('public')->delete($system->image);
            }
            $system->image = $request->file('image')->store('roller_shutter_systems', 'public');
        }

        $system->save();

        return response()->json(['success' => true, 'system' => $system]);
    }

    public function destroyRollerShutterSystem($slug, $id)
    {
        $category = Category::where('slug', $slug)->firstOrFail();
        $system = RollerShutterSystem::where('id', $id)
            ->where('category_id', $category->id)
            ->firstOrFail();

        if ($system->image) {
            Storage::disk('public')->delete($system->image);
        }
        $system->delete();

        return response()->json(['success' => true]);
    }

}
