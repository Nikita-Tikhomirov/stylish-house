<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Subcategory;
use App\Models\ProdModel;
use App\Models\Tab;
use App\Models\Category;
use App\Models\ThrouElement;
use App\Models\HomePage;
use App\Models\IconCard;
use App\Support\CanonicalUrl;
use Illuminate\Support\Facades\Schema;


use App\Services\CartService;
use App\Services\ProductImageThumbnailService;





class ProductController extends Controller
{
    protected $cartService;

    /**
     * Display a listing of the resource.
     */
    public function index($product_slug)
    {
        // Найти продукт по slug
        $product = Product::where('slug', $product_slug)->firstOrFail();
        $subcategories = Subcategory::all();
        $products = Product::all();
        $models = ProdModel::all();

        $tabs = $product->tabs;
        // Получаем ID категории через подкатегорию


        // Вернуть представление с продуктом для редактирования
        return view('admin.prodEdit', compact('product', 'subcategories', 'products', 'models', 'tabs'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $subcategories = Subcategory::all();
        return view('admin.addProd', compact('subcategories'));


    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:products,slug',
            'description' => 'nullable|string',
            'first_screenn_description' => 'required|string',
            'h1' => 'required|string|max:255',
            'coef' => 'required|string|max:255',
            'subcategory_id' => 'required|exists:subcategories,id',
        ]);

        $subcategory = Subcategory::findOrFail($request->subcategory_id);

        // Получаем ID категории через подкатегорию
        $categoryId = $subcategory->category->id;
        // Создаем новую категорию с данными, кроме изображения
        $product = Product::create([
            'title' => $request->title,
            'slug' => $request->slug,
            'description' => $request->description,
            'first_screenn_description' => $request->first_screenn_description,
            'h1' => $request->h1,
            'coef' => $request->coef,
            'subcategory_id' => $request->subcategory_id,
            'category_id' => $categoryId,
            'show_in_menu' => $request->show_in_menu ?? true,
            'show_in_catalog' => $request->show_in_catalog ?? true,
        ]);

        // Редиректим на страницу редактирования только что созданной категории
        return redirect()->route('product.index', ['product_slug' => $product->slug]);


    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $category_slug, string $subcategory_slug, string $product_slug)
    {
        // Убираем слэш в конце если есть
        $product_slug = rtrim($product_slug, '/');
        
        // Поиск товара по slug с проверкой связей
        $product = Product::where('products.slug', $product_slug)
            ->leftJoin('prod_model', 'products.model_id', '=', 'prod_model.id')
            ->select('products.*', 'prod_model.title as model_title')
            ->with(['category', 'subcategory'])
            ->firstOrFail();

        $category = $product->category;
        $subcategory = $product->subcategory;
        abort_unless(
            $category
                && $subcategory
                && (int) $product->category_id === (int) $subcategory->category_id,
            404
        );

        $target = CanonicalUrl::route('product.show', [
            'category_slug' => $category->slug,
            'subcategory_slug' => $subcategory->slug,
            'product_slug' => $product->slug,
        ], false);

        if (CanonicalUrl::requestPath($request) !== $target) {
            return redirect()->away(CanonicalUrl::withQueryString(
                $target,
                (string) $request->server->get('QUERY_STRING', '')
            ), 301);
        }
            
        $tabs = $product->tabs()
            // ->where('title', '!=', 'Описание')
            // ->where('title', '!=', 'Бесплатный замер')
            ->get();

        $relatedProductIds = $product->related_product_ids; // Предполагается, что это массив ID
        $relatedProducts = [];
        if (!empty($relatedProductIds) && is_array($relatedProductIds)) {
            $relatedProducts = Product::whereIn('products.id', $relatedProductIds)
                ->leftJoin('prod_model', 'products.model_id', '=', 'prod_model.id')
                ->select('products.*', 'prod_model.title as model_title')
                ->get();
        }

        $altProducts = Product::where('category_id', '!=', $product->category_id)
            ->leftJoin('prod_model', 'products.model_id', '=', 'prod_model.id')
            ->select('products.*', 'prod_model.title as model_title')
            ->inRandomOrder()
            ->limit(10)
            ->get();

        $cart = $request->session()->get('cart', []);

        $sameModelProducts = [];
        if ($product->model_id) {
            $sameModelProducts = Product::where('model_id', $product->model_id)
                ->where('subcategory_id', $product->subcategory_id)
                ->where('id', '!=', $product->id)
                ->inRandomOrder()
                ->limit(4)
                ->get();
        }

        $seamlesProds = [];
        if ($product->model_id) {
            $seamlesProds = Product::where('products.model_id', $product->model_id)
                ->where('products.subcategory_id', $product->subcategory_id)
                ->where('products.id', '!=', $product->id)
                ->leftJoin('prod_model', 'products.model_id', '=', 'prod_model.id')
                ->select('products.*', 'prod_model.title as model_title')
                ->inRandomOrder()
                ->limit(10)
                ->get();
        }

        $model = null;
        if ($product->model_id) {
            $model = ProdModel::find($product->model_id);
        }
        $headerInfo = ThrouElement::firstOrFail();
        $homePageFields = HomePage::firstOrFail();
        $iconCards = IconCard::all();
        $curtainSubcats = Subcategory::whereIn('id', $headerInfo->curtain_subcategories ?? [])->with('category')->get();
        $blindSubcats = Subcategory::whereIn('id', $headerInfo->blind_subcategories ?? [])->with('category')->get();

        // Определяем шаблон в зависимости от категории
        $template = $product->category_id == 16 ? 'front.product-plumbing' : 'front.product';

        return view($template, compact('product', 'tabs', 'relatedProducts', 'altProducts', 'cart', 'sameModelProducts', 'category', 'model', 'headerInfo', 'seamlesProds', 'homePageFields', 'iconCards','curtainSubcats','blindSubcats'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request)
    {
        $query = Product::query();

        // Фильтрация по категории
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        // Фильтрация по подкатегории
        if ($request->filled('subcategory')) {
            $query->where('subcategory_id', $request->subcategory);
        }

        // Поиск по названию
        if ($request->filled('search')) {
            $query->where('h1', 'like', '%' . $request->search . '%');
        }

        $products = $query->paginate(10)->appends($request->query());

        if ($request->ajax()) {
            return response()->json([
                'products' => view('admin.partials.products', compact('products'))->render(),
                'pagination' => (string) $products->withQueryString()->links(),
            ]);
        }

        $categories = Category::all();
        $subcategories = Subcategory::all();

        return view('admin.prods', compact('products', 'categories', 'subcategories'));
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $product_slug)
    {

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'first_screenn_description' => 'required|string|max:255',
            // 'slug' => 'required|string|max:255|',
            'h1' => 'required|string|max:255',
            'coef' => 'required|string|max:255',
            'subcategory' => 'required|string|max:255'
        ]);

        $product = Product::where('slug', $product_slug)->firstOrFail();
        $catId = Subcategory::find($request->subcategory)->category_id;
        $product->update([
            'title' => $request->title,
            'description' => $request->description,
            'first_screenn_description' => $request->first_screenn_description,
            'h1' => $request->h1,
            'coef' => $request->coef,
            // 'slug' => $request->slug,
            'subcategory_id' => $request->subcategory,
            'category_id' => $catId
        ]);

        return response()->json(['message' => 'Product updated successfully!', 'product_slug' => $product->slug]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $product_slug)
    {
        $product = Product::where('slug', $product_slug)->firstOrFail();
        $product->delete();

        return response()->json(['success' => true, 'message' => 'Продукт успешно удалён']);
    }


    public function updateVisibility(Request $request, $product_slug)
    {
        // Находим продукт по slug
        $product = Product::where('slug', $product_slug)->firstOrFail();

        // Валидация входящих данных
        $data = $request->validate([
            'show_in_menu' => 'required|boolean',
            'show_in_catalog' => 'required|boolean',
            'related_product_ids' => 'nullable|array',
            'alternative_product_ids' => 'nullable|array',
            'discount' => 'nullable|numeric',
            'home_actions' => 'nullable|string',
            'home_populars' => 'nullable|string',
        ]);

        // Сохраняем данные в модель
        $product->show_in_menu = $data['show_in_menu'];
        $product->show_in_catalog = $data['show_in_catalog'];
        $product->related_product_ids = $data['related_product_ids'] ?? [];
        $product->alternative_product_ids = $data['alternative_product_ids'] ?? [];
        $product->discount = $data['discount'];
        $product->home_actions = $data['home_actions'];
        $product->home_populars = $data['home_populars'];

        // Сохраняем изменения в базе данных
        $product->save();

        return response()->json(['message' => 'Товар успешно обновлен']);
    }

    public function updateCalcParametrs(Request $request, $product_slug)
    {
        // Находим продукт по slug
        $product = Product::where('slug', $product_slug)->firstOrFail();

        // Валидация входящих данных
        $data = $request->validate([
            'xlscolor' => 'nullable|string',
            'cloth' => 'nullable|string',
            'control' => 'nullable|string',
            'material' => 'nullable|string',

        ]);

        // Сохраняем данные в модель
        $product->xlscolor = $data['xlscolor'];
        $product->cloth = $data['cloth'];
        $product->control = $data['control'];
        $product->material = $data['material'];




        // Сохраняем изменения в базе данных
        $product->save();

        return response()->json(['message' => 'Товар успешно обновлен']);
    }

    public function getModelImage($modelId)
    {
        $model = ProdModel::find($modelId);

        if (!$model) {
            return response()->json(['error' => 'Model not found'], 404);
        }

        // Предполагаем, что путь к изображению хранится в свойстве `image` модели
        $imagePath = asset('storage/' . $model->image);

        // Передаем также координаты маски из модели
        $maskCoordinates = $model->mask_coordinates;

        return response()->json([
            'image' => $imagePath,
            'mask_coordinates' => $maskCoordinates
        ]);
    }

    public function saveProductImage(Request $request, $id)
    {
        $data = $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'model_id' => 'required|integer',
            'color' => 'required|string'
        ]);

        // Найти товар по ID из маршрута
        $product = Product::findOrFail($id);

        // Генерируем имя файла и сохраняем изображение
        $imageName = time() . '.' . $request->image->extension();
        $request->file('image')->storeAs('products', $imageName, 'public');

        // Обновляем данные товара
        $product->image_path = 'storage/products/' . $imageName;
        $product->model_id = $request->model_id;
        $product->color = $request->color;
        $product->save();

        $thumbnailResult = app(ProductImageThumbnailService::class)->generateForProduct($product);
        if ($this->canStoreThumbColumns() && !empty($thumbnailResult['thumbnail_public_path'])) {
            $product->image_thumb_path = $thumbnailResult['thumbnail_public_path'];
            $product->save();
        }

        return response()->json([
            'message' => 'Image saved successfully!',
            'image_path' => $product->image_path,
            'thumbnail_path' => $thumbnailResult['thumbnail_public_path'] ?? null,
            'thumbnail_status' => $thumbnailResult['status'] ?? null,
        ]);
    }


    public function updatePhotos(Request $request, string $product_slug)
    {
        $product = Product::where('slug', $product_slug)->firstOrFail();

        if (!$request->hasFile('image_path') && !$request->hasFile('fabric_photo')) {
            return response()->json(['message' => 'No files provided'], 422);
        }

        $request->validate([
            'image_path' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
            'fabric_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
        ]);

        $updated = false;

        $canStoreThumbColumns = $this->canStoreThumbColumns();

        if ($request->hasFile('image_path')) {
            $mainStoredPath = $request->file('image_path')->store('products', 'public');
            $product->image_path = 'storage/' . $mainStoredPath;

            $mainThumb = app(ProductImageThumbnailService::class)->generateFromPath($product->image_path);
            if ($canStoreThumbColumns) {
                $product->image_thumb_path = $mainThumb['thumbnail_public_path'] ?? null;
            }
            $updated = true;
        }

        if ($request->hasFile('fabric_photo')) {
            $fabricStoredPath = $request->file('fabric_photo')->store('products/materials', 'public');
            $product->fabric_photo = 'storage/' . $fabricStoredPath;

            $fabricThumb = app(ProductImageThumbnailService::class)->generateFromPath($product->fabric_photo);
            if ($canStoreThumbColumns) {
                $product->fabric_thumb_path = $fabricThumb['thumbnail_public_path'] ?? null;
            }
            $updated = true;
        }

        if ($updated) {
            $product->save();
        }

        return response()->json([
            'message' => 'Photos updated successfully',
            'image_path' => $product->image_path ? $this->encodeImagePath($product->image_path) : null,
            'image_thumb_path' => ($canStoreThumbColumns && $product->image_thumb_path) ? $this->encodeImagePath($product->image_thumb_path) : null,
            'fabric_photo' => $product->fabric_photo ? $this->encodeImagePath($product->fabric_photo) : null,
            'fabric_thumb_path' => ($canStoreThumbColumns && $product->fabric_thumb_path) ? $this->encodeImagePath($product->fabric_thumb_path) : null,
        ]);
    }


    public function thumbnailsPage(Request $request)
    {
        $categories = Category::orderBy('titleh1')->get(['id', 'titleh1']);
        $subcategories = Subcategory::orderBy('titleh1')->get(['id', 'titleh1', 'category_id']);

        return view('admin.product-thumbnails', compact('categories', 'subcategories'));
    }

    public function thumbnailsProcess(Request $request)
    {
        $data = $request->validate([
            'last_id' => 'nullable|integer|min:0',
            'limit' => 'nullable|integer|min:1|max:500',
            'width' => 'nullable|integer|min:50|max:2000',
            'height' => 'nullable|integer|min:50|max:2000',
            'category_id' => 'nullable|integer|min:1',
            'subcategory_id' => 'nullable|integer|min:1',
            'force' => 'nullable|boolean',
            'include_main' => 'nullable|boolean',
            'include_fabric' => 'nullable|boolean',
        ]);

        $lastId = (int)($data['last_id'] ?? 0);
        $limit = (int)($data['limit'] ?? 100);
        $width = (int)($data['width'] ?? 600);
        $height = (int)($data['height'] ?? 600);
        $categoryId = isset($data['category_id']) ? (int)$data['category_id'] : null;
        $subcategoryId = isset($data['subcategory_id']) ? (int)$data['subcategory_id'] : null;
        $force = (bool)($data['force'] ?? false);
        $includeMain = (bool)($data['include_main'] ?? true);
        $includeFabric = (bool)($data['include_fabric'] ?? true);
        $canStoreThumbColumns = $this->canStoreThumbColumns();

        if (!$includeMain && !$includeFabric) {
            return response()->json([
                'message' => 'Nothing selected for processing',
            ], 422);
        }

        $query = Product::query();

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        if ($subcategoryId) {
            $query->where('subcategory_id', $subcategoryId);
        }

        $query->where(function ($q) use ($includeMain, $includeFabric, $force, $canStoreThumbColumns) {
            if ($includeMain) {
                $q->where(function ($q2) use ($force, $canStoreThumbColumns) {
                    $q2->whereNotNull('image_path')->where('image_path', '<>', '');
                    if ($canStoreThumbColumns && !$force) {
                        $q2->where(function ($q3) {
                            $q3->whereNull('image_thumb_path')->orWhere('image_thumb_path', '');
                        });
                    }
                });
            }
            if ($includeFabric) {
                $method = $includeMain ? 'orWhere' : 'where';
                $q->{$method}(function ($q2) use ($force, $canStoreThumbColumns) {
                    $q2->whereNotNull('fabric_photo')->where('fabric_photo', '<>', '');
                    if ($canStoreThumbColumns && !$force) {
                        $q2->where(function ($q3) {
                            $q3->whereNull('fabric_thumb_path')->orWhere('fabric_thumb_path', '');
                        });
                    }
                });
            }
        });

        $total = (clone $query)->count();

        $items = (clone $query)
            ->where('id', '>', $lastId)
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $service = app(ProductImageThumbnailService::class);

        $processed = 0;
        $generatedMain = 0;
        $generatedFabric = 0;
        $skipped = 0;
        $errors = 0;
        $errorItems = [];
        $lastProcessedId = $lastId;

        foreach ($items as $product) {
            $lastProcessedId = (int)$product->id;
            $processed++;
            $changed = false;

            if ($includeMain && !empty($product->image_path)) {
                $mainResult = $service->generateFromPath($product->image_path, [
                    'force' => $force,
                    'width' => $width,
                    'height' => $height,
                ]);
                $mainStatus = $mainResult['status'] ?? null;

                if ($mainStatus === 'generated') {
                    $generatedMain++;
                    if ($canStoreThumbColumns && !empty($mainResult['thumbnail_public_path']) && $product->image_thumb_path !== $mainResult['thumbnail_public_path']) {
                        $product->image_thumb_path = $mainResult['thumbnail_public_path'];
                        $changed = true;
                    }
                } elseif ($mainStatus === 'error') {
                    $errors++;
                    $errorItems[] = [
                        'product_id' => $product->id,
                        'type' => 'main',
                        'reason' => $mainResult['reason'] ?? 'error',
                    ];
                } else {
                    $skipped++;
                }
            }

            if ($includeFabric && !empty($product->fabric_photo)) {
                $fabricResult = $service->generateFromPath($product->fabric_photo, [
                    'force' => $force,
                    'width' => $width,
                    'height' => $height,
                ]);
                $fabricStatus = $fabricResult['status'] ?? null;

                if ($fabricStatus === 'generated') {
                    $generatedFabric++;
                    if ($canStoreThumbColumns && !empty($fabricResult['thumbnail_public_path']) && $product->fabric_thumb_path !== $fabricResult['thumbnail_public_path']) {
                        $product->fabric_thumb_path = $fabricResult['thumbnail_public_path'];
                        $changed = true;
                    }
                } elseif ($fabricStatus === 'error') {
                    $errors++;
                    $errorItems[] = [
                        'product_id' => $product->id,
                        'type' => 'fabric',
                        'reason' => $fabricResult['reason'] ?? 'error',
                    ];
                } else {
                    $skipped++;
                }
            }

            if ($changed) {
                $product->save();
            }
        }

        $done = $items->count() < $limit;

        return response()->json([
            'total' => $total,
            'processed' => $processed,
            'next_last_id' => $lastProcessedId,
            'done' => $done,
            'stats' => [
                'generated_main' => $generatedMain,
                'generated_fabric' => $generatedFabric,
                'skipped' => $skipped,
                'errors' => $errors,
            ],
            'errors_sample' => array_slice($errorItems, 0, 20),
        ]);
    }
    public function updateSeoSection(Request $request, $product_slug)
    {
        // Find the subcategory by both category and subcategory slug
        $product = Product::where('slug', $product_slug)->firstOrFail();



        // Validate and update SEO section
        $request->validate([
            'seo' => 'required|string',
        ]);

        $product->seo = $request->input('seo');
        $product->save();

        return response()->json(['message' => 'Контент успешно обновлен!'], 200);
    }

    function encodeImagePath(string|null $path): ?string
    {
        if (!$path)
            return null;

        // Убираем ведущий слэш, если есть
        $cleanPath = ltrim($path, '/');

        // Разбиваем на части
        $dir = dirname($cleanPath);
        $file = basename($cleanPath);

        // rawurlencode только для имени файла
        $encodedFile = rawurlencode($file);

        // Формируем путь без лишних слэшей
        $finalPath = ($dir === '.' ? '' : $dir . '/') . $encodedFile;

        // Возвращаем абсолютный URL сайта
        return asset($finalPath);
    }

    public function getProdToPopup($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        $relatedProducts = $product->relatedProducts()->limit(4)->get();

        $gallery = $relatedProducts->map(function ($related) {
            return [
                'image' => $this->encodeImagePath($related->image_path),
                'fabric_photo' => $this->encodeImagePath($related->fabric_photo),
                'link' => CanonicalUrl::route('product.show', [
                    'category_slug' => $related->category->slug,
                    'subcategory_slug' => $related->subcategory->slug,
                    'product_slug' => $related->slug,
                ]),
            ];
        });

        $model = ProdModel::find($product->model_id);
        $modelTitle = $model ? $model->title : null;

        return response()->json([
            'title' => $product->h1,
            'first_screenn_description' => $product->first_screenn_description,
            'image_path' => $this->encodeImagePath($product->image_path),
            'gallery' => $gallery,
            'model' => $modelTitle,
            'cloth' => $product->cloth,
            'discount' => $product->discount,
            'min_price' => $product->min_price,
            'model_id' => $product->model_id,
            'fabric_photo' => $this->encodeImagePath($product->fabric_photo),
            'min_width' => $product->min_width,
            'min_height' => $product->min_height,
            'installation_type' => $product->installation_type,
            'overhead_price' => $product->overhead_price,
            'builtin_price' => $product->builtin_price,
            'control_type' => $product->control_type,
            'strap_price' => $product->strap_price,
            'cardan_price' => $product->cardan_price,
            'pim_price' => $product->pim_price,
            'electric_price' => $product->electric_price,
            'lock_device' => $product->lock_device,
            'rigel_price' => $product->rigel_price,
            'shchyolka_price' => $product->shchyolka_price,
            'upper_price' => $product->upper_price,
            'ral_paint' => (bool) $product->ral_paint,
            'photo_print' => (bool) $product->photo_print,
            'ral_price' => $product->ral_price,
            'photo_price' => $product->photo_price,
        ]);
    }

    public function updateRolshveniParams(Request $request, $id)
    {
        $product = Product::find($id);
        
        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        $validated = $request->validate([
            'installation_type' => 'nullable|string|in:overhead,built-in',
            'control_type' => 'nullable|string|in:strap,cardan,pim,electric',
            'lock_device' => 'nullable|string|in:rigel,shchyolka,upper,none',
            // Цены монтажа
            'overhead_price' => 'nullable|numeric|min:0',
            'builtin_price' => 'nullable|numeric|min:0',
            // Цены управления
            'strap_price' => 'nullable|numeric|min:0',
            'cardan_price' => 'nullable|numeric|min:0',
            'pim_price' => 'nullable|numeric|min:0',
            'electric_price' => 'nullable|numeric|min:0',
            // Цены блокирующих устройств
            'rigel_price' => 'nullable|numeric|min:0',
            'shchyolka_price' => 'nullable|numeric|min:0',
            'upper_price' => 'nullable|numeric|min:0',
            // Дополнительные опции
            'ral_paint' => 'nullable|boolean',
            'photo_print' => 'nullable|boolean',
            'ral_price' => 'nullable|numeric|min:0',
            'photo_price' => 'nullable|numeric|min:0',
        ]);

        $product->update($validated);

        return response()->json(['message' => 'Параметры рольставен успешно обновлены']);
    }

    private function canStoreThumbColumns(): bool
    {
        static $supported = null;
        if ($supported === null) {
            $supported = Schema::hasColumn('products', 'image_thumb_path')
                && Schema::hasColumn('products', 'fabric_thumb_path');
        }

        return $supported;
    }
}
