<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use App\Models\Subcategory;
use App\Models\Category;
use App\Models\Faq;
use App\Models\VideoReviews;
use App\Models\WorkExample;
use App\Models\Review;
use App\Models\IconCard;
use App\Models\HomePage;
use App\Models\Product;
use App\Models\ProdModel;
use App\Models\SubcategoryInstallationType;
use App\Services\CartService;
use App\Support\PreviewCardData;
use App\Models\Fabric;
use App\Models\ThrouElement;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
// use App\Models\ProdModel;





class SubcategoryController extends Controller
{
    protected $cartService;

    protected const DEFAULT_FRONT_TEMPLATE = 'front.subcategory';
    protected const DEFAULT_EDIT_TEMPLATE = 'admin.subcatEdit';
    protected const TEMPLATE_CATEGORY_ID = 16;
    protected const TEMPLATE_MIN_VARIANT = 1;
    protected const TEMPLATE_MAX_VARIANT = 4;

    protected function serializePreviewProduct(Product $product): array
    {
        return PreviewCardData::fromProduct($product);
    }

    protected function resolveTemplateVariant(Subcategory $subcategory): int
    {
        $variant = (int) ($subcategory->template_variant ?? self::TEMPLATE_MIN_VARIANT);
        if ($variant < self::TEMPLATE_MIN_VARIANT || $variant > self::TEMPLATE_MAX_VARIANT) {
            return self::TEMPLATE_MIN_VARIANT;
        }

        return $variant;
    }

    protected function resolveTemplateBySubcategory(Subcategory $subcategory, string $context): string
    {
        $defaultTemplate = $context === 'edit'
            ? self::DEFAULT_EDIT_TEMPLATE
            : self::DEFAULT_FRONT_TEMPLATE;

        if ((int) $subcategory->category_id !== self::TEMPLATE_CATEGORY_ID) {
            return $defaultTemplate;
        }

        $variant = (int) ($subcategory->template_variant ?? self::TEMPLATE_MIN_VARIANT);
        if ($variant < self::TEMPLATE_MIN_VARIANT || $variant > self::TEMPLATE_MAX_VARIANT) {
            $variant = self::TEMPLATE_MIN_VARIANT;
        }

        $template = $context === 'edit'
            ? "admin.subcatEdit-template-{$variant}"
            : "front.subcategory-template-{$variant}";

        return View::exists($template) ? $template : $defaultTemplate;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = Category::all(); // Get all categories
        return view('admin.subcatCreate', compact('categories'));

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            // Валидация данных
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'slug' => 'required|string|max:255|unique:subcategories,slug', 
                'description' => 'nullable|string',
                'img' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
                'first_screen_text' => 'required|string|max:255',
                'titleh1' => 'required|string|max:255',
                'category_id' => 'required|exists:categories,id'
            ]);

            // Создание подкатегории
            $subcategory = Subcategory::create($validated);

            // Загрузка изображения
            if ($request->hasFile('img')) {
                $imagePath = $request->file('img')->store('categories', 'public');
                $subcategory->img = $imagePath;
                $subcategory->save();
            }

            return redirect()->route('subcategories.edit', [
                'category_slug' => $subcategory->category->slug,
                'subcategory_slug' => $subcategory->slug
            ])->with('success', 'Подкатегория успешно создана');

        } catch (\Exception $e) {
            // Ловим исключение и возвращаемся с ошибкой
            return back()->withInput()->with('error', 'Ошибка при создании подкатегории: ' . $e->getMessage());
        }
    }



    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $category_slug, string $subcategory_slug)
    {
        $subcategory = Subcategory::where('slug', $subcategory_slug)->firstOrFail();
        $templateVariant = $this->resolveTemplateVariant($subcategory);
        $isRolletCategory = (int) $subcategory->category_id === self::TEMPLATE_CATEGORY_ID;
        $useOwnProductsForTemplate = $isRolletCategory && $templateVariant === 2;

        // Для шаблона 2 всегда работаем с товарами текущей подкатегории.
        $sourceSubcategoryId = $useOwnProductsForTemplate
            ? $subcategory->id
            : ($subcategory->clone_subcategory_id ?: $subcategory->id);

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

        $reviews = Review::all();
        $homePageFields = HomePage::firstOrFail();
        $iconCards = IconCard::all();
        $faqs = Faq::where('subcategory_id', $subcategory->id)->get();
        $workExamples = WorkExample::where('subcategory_id', $subcategory->id)->get();
        $videoReviews = VideoReviews::where('subcategory_id', $subcategory->id)->get();
        $installationTypes = SubcategoryInstallationType::where('subcategory_id', $sourceSubcategoryId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
        $category = Category::where('slug', $category_slug)->firstOrFail();

        $filterColors = Product::where('subcategory_id', $sourceSubcategoryId)
            ->distinct()
            ->pluck('color');

        $models = ProdModel::whereHas('products', function ($query) use ($sourceSubcategoryId) {
            $query->where('subcategory_id', $sourceSubcategoryId);
        })->get();

        $cart = $request->session()->get('cart', []);

        $firstProduct = null;
        $sameModelProducts = collect();

        if ($subcategory->calc_prod) {
            // Если calc_prod заполнен
            $firstProduct = Product::leftJoin('prod_model', 'products.model_id', '=', 'prod_model.id')
                ->select('products.*', 'prod_model.title as model_title', 'prod_model.id as model_id')
                ->where('products.id', $subcategory->calc_prod)
                ->first();

            if ($firstProduct) {
                $sameModelProducts = Product::where('subcategory_id', $sourceSubcategoryId)
                    ->where('model_id', $firstProduct->model_id)
                    ->where('id', '!=', $firstProduct->id) // Исключаем первый товар из выборки
                    ->limit(3) // Ограничиваем до 3, чтобы вместе с первым было 4
                    ->get();

                // Добавляем первый товар в начало коллекции
                $sameModelProducts->prepend($firstProduct);
            } else {
                // Если товар с указанным ID не найден, получаем случайные товары из этой подкатегории
                $sameModelProducts = Product::where('subcategory_id', $sourceSubcategoryId)
                    ->inRandomOrder()
                    ->limit(4)
                    ->get();
            }
        } else {
            // Если calc_prod не заполнен, получаем случайный товар из этой подкатегории
            $firstProduct = Product::where('subcategory_id', $sourceSubcategoryId)
                ->inRandomOrder()
                ->first();

            if ($firstProduct) {
                $sameModelProducts = Product::where('subcategory_id', $sourceSubcategoryId)
                    ->where('model_id', $firstProduct->model_id)
                    ->where('id', '!=', $firstProduct->id) // Исключаем первый товар из выборки
                    ->limit(3) // Ограничиваем до 3, чтобы вместе с первым было 4
                    ->get();

                // Добавляем первый товар в начало коллекции
                $sameModelProducts->prepend($firstProduct);
            } else {
                // Если в подкатегории нет товаров, возвращаем пустую коллекцию
                $sameModelProducts = collect();
            }
        }

        // Фильтрация материалов
        $materials = Product::where('subcategory_id', $sourceSubcategoryId)
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

        // Обновляем фильтрацию
        $filterProduts = Product::where('subcategory_id', $sourceSubcategoryId)
            ->when(!empty($subcategory->start_material), function ($query) use ($subcategory) {
                $query->where('material', $subcategory->start_material);
            })
            ->when(!empty($subcategory->filter_color), function ($query) use ($subcategory) {
                $query->where('color', $subcategory->filter_color);
            })
            ->when(!empty($subcategory->model_id_to_filter), function ($query) use ($subcategory) {
                $models = json_decode($subcategory->model_id_to_filter, true);
                if (!empty($models)) {
                    $query->whereIn('model_id', $models);
                }
            })
            ->leftJoin('prod_model', 'products.model_id', '=', 'prod_model.id')
            ->select('products.*', 'prod_model.title as model_title')
            ->with(['category', 'subcategory'])
            ->paginate(12);

        $maxFilterPrice = (int) Product::where('subcategory_id', $sourceSubcategoryId)
            ->when(!empty($subcategory->start_material), function ($query) use ($subcategory) {
                $query->where('material', $subcategory->start_material);
            })
            ->when(!empty($subcategory->filter_color), function ($query) use ($subcategory) {
                $query->where('color', $subcategory->filter_color);
            })
            ->when(!empty($subcategory->model_id_to_filter), function ($query) use ($subcategory) {
                $models = json_decode($subcategory->model_id_to_filter, true);
                if (!empty($models)) {
                    $query->whereIn('model_id', $models);
                }
            })
            ->whereNotNull('min_price')
            ->where('min_price', '>', 0)
            ->max('min_price');

        $subcategoriesWithProducts = collect();
        if ($isRolletCategory) {
            $categorySubcategories = Subcategory::where('category_id', $subcategory->category_id)
                ->orderBy('id')
                ->get();

            $subcategoriesWithProducts = $categorySubcategories->map(function ($subcat) {
                $productsSourceId = $subcat->clone_subcategory_id ?: $subcat->id;
                $products = Product::where('subcategory_id', $productsSourceId)
                    ->with(['category', 'subcategory'])
                    ->limit(8)
                    ->get();

                return [
                    'subcategory' => $subcat,
                    'products' => $products,
                ];
            });
        }


        $seoCats = Subcategory::whereIn('id', $subcategory->related_subcategory_ids ?? [])->get();
        $curtainSubcats = Subcategory::whereIn('id', $headerInfo->curtain_subcategories ?? [])->with('category')->get();
        $blindSubcats = Subcategory::whereIn('id', $headerInfo->blind_subcategories ?? [])->with('category')->get();
        $selectedModels = json_decode($subcategory->model_id_to_filter, true) ?? [];
        
        // Resolve front template by subcategory mapping with fallback to default template.
        $frontTemplate = $this->resolveTemplateBySubcategory($subcategory, 'front');

        return view($frontTemplate, compact('subcategory', 'categoriesInCatalogMenu', 'categoriesInHeaderMenu', 'reviews', 'homePageFields', 'iconCards', 'faqs', 'workExamples', 'category', 'videoReviews', 'installationTypes', 'subcategoriesWithProducts', 'filterProduts', 'filterColors', 'models', 'cart', 'firstProduct', 'sameModelProducts', 'materials', 'headerInfo', 'seoCats', 'curtainSubcats', 'blindSubcats','selectedModels', 'maxFilterPrice'));
    }
    /**
     * Show the form for editing the specified resource.
     */
    public function edit($category_slug, $subcategory_slug)
    {
        $categories = Category::all();
        $category = Category::where('slug', $category_slug)->firstOrFail();
        $subcategory = Subcategory::where('slug', $subcategory_slug)->where('category_id', $category->id)->firstOrFail();
        $workExamples = WorkExample::where('subcategory_id', $subcategory->id)->get(); // Примеры работ по подкатегории
        $videoReviews = VideoReviews::all();

        $subcategories = Subcategory::all();

        $faqs = Faq::where('subcategory_id', $subcategory->id)->get();

        $relatedIds = $subcategory->related_subcategory_ids ?? [];

        $sourceSubcategoryId = $subcategory->clone_subcategory_id ?: $subcategory->id;

        $models = ProdModel::all();

        $relatedIds = $subcategory->model_id_to_filter ? json_decode($subcategory->model_id_to_filter, true) : [];
        $installationTypes = SubcategoryInstallationType::where('subcategory_id', $subcategory->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $materials = Product::where('subcategory_id', $sourceSubcategoryId)
            ->whereNotNull('material')
            ->distinct()
            ->pluck('material'); // Коллекция строк, например ['Ткань 1', 'Ткань 2']

        $editTemplate = $this->resolveTemplateBySubcategory($subcategory, 'edit');

        return view($editTemplate, compact('categories', 'category', 'subcategory', 'videoReviews', 'workExamples', 'faqs', 'subcategories', 'relatedIds', 'materials', 'models', 'relatedIds', 'installationTypes'));
    }
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $category_slug, $subcategory_slug)
    {
        // Валидация входящих данных
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'slug' => 'required|string|max:255|unique:subcategories,slug,' . $subcategory_slug . ',slug',
            'titleh1' => 'nullable|string|max:255',
            // 'menu_title' => 'nullable|string|max:255',
            'first_screen_text' => 'nullable|string',
            'img' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            // 'clone_subcategory_id' => 'nullable|string|max:255'
        ]);

        // Находим категорию и подкатегорию
        $category = Category::where('slug', $category_slug)->firstOrFail();
        $subcategory = Subcategory::where('slug', $subcategory_slug)
            ->where('category_id', $category->id)
            ->firstOrFail();

        // Обновление полей подкатегории
        $subcategory->title = $request->input('title');
        $subcategory->description = $request->input('description');
        $subcategory->slug = $request->input('slug');
        $subcategory->titleh1 = $request->input('titleh1');
        $subcategory->first_screen_text = $request->input('first_screen_text');
        $subcategory->menu_title = $request->input('menu_title');

        // $subcategory->clone_subcategory_id = $request->input('clone_subcategory_id');

        // Если загружено изображение, обрабатываем его
        if ($request->hasFile('img')) {
            $imagePath = $request->file('img')->store('categories', 'public');
            $subcategory->img = $imagePath;
        }

        // Сохраняем подкатегорию
        $subcategory->save();

        // Возвращаем JSON-ответ
        return response()->json(['success' => 'Подкатегория успешно обновлена']);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($category_slug, $subcategory_slug)
    {
        // Найти подкатегорию по slug
        $subcategory = Subcategory::where('slug', $subcategory_slug)->firstOrFail();

        // Удалить подкатегорию
        $subcategory->delete();

        // Вернуть пустой успешный ответ
        return response()->noContent();
    }

    public function updateSeoSection(Request $request, $category_slug, $subcategory_slug)
    {
        // Find the subcategory by both category and subcategory slug
        $category = Category::where('slug', $category_slug)->firstOrFail();
        $subcategory = Subcategory::where('slug', $subcategory_slug)
            ->where('category_id', $category->id)
            ->firstOrFail();

        // Validate and update SEO section
        $request->validate([
            'seo' => 'required|string',
        ]);

        $subcategory->seo = $request->input('seo');
        $subcategory->save();

        return response()->json(['message' => 'Контент успешно обновлен!'], 200);
    }

    public function uploadSeoImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
        ]);

        $path = $request->file('image')->store('subcategory-seo', 'public');

        return response()->json([
            'url' => Storage::url($path),
        ]);
    }

    public function updateFaqSection(Request $request, $category_slug, $subcategory_slug)
    {
        // Find the subcategory by both category and subcategory slug
        $category = Category::where('slug', $category_slug)->firstOrFail();
        $subcategory = Subcategory::where('slug', $subcategory_slug)
            ->where('category_id', $category->id)
            ->firstOrFail();

        // Validate and update FAQ section
        $request->validate([
            'faq_html' => 'required|string',
        ]);

        $subcategory->faq_html = $request->input('faq_html');
        $subcategory->save();

        return response()->json(['message' => 'FAQ контент успешно обновлен!'], 200);
    }

    public function updateVisibility(Request $request, $category_slug, $subcategory_slug)
    {
        $request->validate([
            'template_variant' => 'nullable|integer|min:1|max:4',
        ]);

        $category = Category::where('slug', $category_slug)->firstOrFail();
        $subcategory = Subcategory::where('slug', $subcategory_slug)
            ->where('category_id', $category->id)
            ->firstOrFail();


        $subcategory->show_in_menu = $request->input('show_in_menu');
        $subcategory->show_in_catalog = $request->input('show_in_catalog');
        $subcategory->clone_subcategory_id = $request->input('clone_subcategory_id');

        // $request->input('all_subcategory_ids');
        $subcategory->related_subcategory_ids = $request->input('all_subcategory_ids'); // Laravel сам приведет массив к JSON
        $subcategory->start_material = $request->input('start_material');
        $subcategory->filter_color = $request->input('filter_color');

        $subcategory->show_in_more_cats = $request->input('show_in_more_cats');
        $subcategory->show_in_cats_filter = $request->input('show_in_cats_filter');

        $subcategory->calc_prod = $request->input('calc_prod');
        $subcategory->model_id_to_filter = json_encode($request->input('model_id_to_filter'));
        $templateVariant = (int) $request->input('template_variant');
        $isAllowedVariant = $templateVariant >= self::TEMPLATE_MIN_VARIANT && $templateVariant <= self::TEMPLATE_MAX_VARIANT;
        $subcategory->template_variant = ((int) $subcategory->category_id === self::TEMPLATE_CATEGORY_ID && $isAllowedVariant)
            ? $templateVariant
            : null;





        $subcategory->save();


        return response()->json(['message' => 'Категория успешно обновлена']);
    }

    public function updateTemplateVariant(Request $request, $category_slug, $subcategory_slug)
    {
        $category = Category::where('slug', $category_slug)->firstOrFail();
        $subcategory = Subcategory::where('slug', $subcategory_slug)
            ->where('category_id', $category->id)
            ->firstOrFail();

        if ((int) $subcategory->category_id !== self::TEMPLATE_CATEGORY_ID) {
            return response()->json([
                'message' => 'Template switching is available only for category 16 subcategories.',
            ], 422);
        }

        $templateVariant = (int) $request->input('template_variant', self::TEMPLATE_MIN_VARIANT);
        if ($templateVariant < self::TEMPLATE_MIN_VARIANT || $templateVariant > self::TEMPLATE_MAX_VARIANT) {
            $templateVariant = self::TEMPLATE_MIN_VARIANT;
        }

        $subcategory->template_variant = $templateVariant;
        $subcategory->save();

        return response()->json([
            'message' => 'Template updated successfully.',
            'template_variant' => $subcategory->template_variant,
            'redirect_url' => route('subcategories.edit', [
                'category_slug' => $category->slug,
                'subcategory_slug' => $subcategory->slug,
            ]),
        ]);
    }

    public function storeInstallationType(Request $request, $category_slug, $subcategory_slug)
    {
        $category = Category::where('slug', $category_slug)->firstOrFail();
        $subcategory = Subcategory::where('slug', $subcategory_slug)
            ->where('category_id', $category->id)
            ->firstOrFail();

        $data = $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('installation_types', 'public');
        }

        $data['subcategory_id'] = $subcategory->id;
        $data['sort_order'] = $data['sort_order'] ?? 0;

        $item = SubcategoryInstallationType::create($data);

        return response()->json([
            'success' => true,
            'item' => $item,
        ]);
    }

    public function updateInstallationType(Request $request, $category_slug, $subcategory_slug, $id)
    {
        $category = Category::where('slug', $category_slug)->firstOrFail();
        $subcategory = Subcategory::where('slug', $subcategory_slug)
            ->where('category_id', $category->id)
            ->firstOrFail();

        $item = SubcategoryInstallationType::where('id', $id)
            ->where('subcategory_id', $subcategory->id)
            ->firstOrFail();

        $data = $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
        ]);

        if ($request->hasFile('image')) {
            if (!empty($item->image)) {
                Storage::disk('public')->delete($item->image);
            }
            $data['image'] = $request->file('image')->store('installation_types', 'public');
        }

        $item->update($data);

        return response()->json([
            'success' => true,
            'item' => $item->fresh(),
        ]);
    }

    public function destroyInstallationType($category_slug, $subcategory_slug, $id)
    {
        $category = Category::where('slug', $category_slug)->firstOrFail();
        $subcategory = Subcategory::where('slug', $subcategory_slug)
            ->where('category_id', $category->id)
            ->firstOrFail();

        $item = SubcategoryInstallationType::where('id', $id)
            ->where('subcategory_id', $subcategory->id)
            ->firstOrFail();

        if (!empty($item->image)) {
            Storage::disk('public')->delete($item->image);
        }

        $item->delete();

        return response()->json([
            'success' => true,
        ]);
    }

    public function filterProducts(Request $request, $id)
    {
        $models = $request->input('models', []);
        $colors = $request->input('colors', []);
        $materials = $request->input('materials', []);
        $page = $request->input('page', 1); // Текущая страница
        $priceFilter = $this->normalizePriceFilter($request);

        // Определяем подкатегорию
        $subcategory = Subcategory::findOrFail($id);

        // Если указана клонируемая подкатегория, заменяем ID
        $targetSubcategoryId = $subcategory->clone_subcategory_id ?: $subcategory->id;

        // Запрос товаров
        $query = Product::with(['category', 'subcategory', 'model'])
            ->where('subcategory_id', $targetSubcategoryId);

        if (!empty($models)) {
            $query->whereIn('model_id', $models);
        }

        if (!empty($colors)) {
            $query->whereIn('color', $colors);
        }

        if (!empty($materials)) {
            $query->whereIn('material', $materials);
        }

        // Пагинация
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
}
