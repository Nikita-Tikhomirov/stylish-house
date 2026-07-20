<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\SubcategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SeoSectionController;
use App\Http\Controllers\IconCardController;
use App\Http\Controllers\FaqHome;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CatsEdit;
use App\Http\Controllers\videoReviewsController;
use App\Http\Controllers\WorkExampleController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\ModelsController;
use App\Http\Controllers\TabsController;
use App\Http\Controllers\FirstScreenSliderController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\ExcelController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\FabrickController;
use App\Http\Controllers\HeaderInfo;
use App\Http\Controllers\SliderController;
use App\Http\Controllers\ProductGenerator;
use App\Http\Controllers\MinPriceRunController;
use App\Http\Controllers\FormController;


use App\Http\Controllers\ColorController;
use App\Http\Controllers\addImgFromGeneration;




use Illuminate\Support\Facades\Storage;


Auth::routes();
Route::get('/sheet-names', [ExcelController::class, 'getProdPrice']);
Route::get('/sheet-names-test', [ExcelController::class, 'test']);


// Route::get('/sheet-names', [ExcelController::class, 'getProdPrice']);
// Route::get('/sheet-data', [ExcelController::class, 'getSheetData']);


Route::middleware('auth')->group(function () {
    Route::get('/profile', [OrderController::class, 'show'])->name('profile.account');
    Route::get('/profile/{id}', [OrderController::class, 'show'])->name('profile.show');
    Route::get('/favorites', [FavoriteController::class, 'index'])->name('favorites.index');
    Route::post('/favorites/sync', [FavoriteController::class, 'sync'])->name('favorites.sync');
    Route::post('/favorites/{product}', [FavoriteController::class, 'store'])->name('favorites.store');
    Route::delete('/favorites/{product}', [FavoriteController::class, 'destroy'])->name('favorites.destroy');
});

Route::get('/popup/{id}', [ProductController::class, 'getProdToPopup']);

Route::get('/shop-pages/{slug}', [PageController::class, 'index'])->name('pages.index');
// Route::get('/admin', [AdminController::class, 'index'])->middleware('role:admin');

Route::middleware(['role:admin'])->group(function () {


    // Главная админская панель
    Route::get('/admin', [AdminController::class, 'index']);

    // Редактирование главной страницы
    Route::get('/admin/home/edit', [AdminController::class, 'show'])->name('home.edit');
    Route::get('/admin/cats/edit', [CatsEdit::class, 'show']);

    Route::post('/admin/home/update-seo-section', [SeoSectionController::class, 'updateTextEditorSection'])->name('home.update.texteditor');
    Route::post('/admin/home/update-delivery-text', [AdminController::class, 'updateDeliveryText'])->name('home.update.deliverytext');
    Route::post('/admin/home/update-request-text', [AdminController::class, 'updateSectionRequest'])->name('home.update.requesttext');

    Route::post('/admin/icon-cards', [IconCardController::class, 'store'])->name('admin.iconCards.store');
    Route::put('/admin/icon-cards/{id}', [IconCardController::class, 'update'])->name('admin.iconCards.update');
    Route::delete('/admin/icon-cards/{id}', [IconCardController::class, 'destroy'])->name('admin.iconCards.destroy');

    Route::post('/admin/homepage/metas', [AdminController::class, 'savemeta'])->name('admin.homepage.savemeta');

    // Пример корректного метода для создания FAQ карточки
    Route::post('/admin/faqs', [FaqHome::class, 'store']);
    Route::put('/admin/faqs/{id}', [FaqHome::class, 'update']);
    Route::delete('/admin/faqs/delete/{id}', [FaqHome::class, 'destroy']);

    Route::post('/admin/reviews', [ReviewController::class, 'store']);
    Route::put('/admin/reviews/{id}', [ReviewController::class, 'update']);
    Route::delete('/admin/reviews/{id}', [ReviewController::class, 'destroy']);

    // Маршруты для редактирования категорий
    Route::get('/admin/categories/create', [CategoryController::class, 'create'])->name('categories.create');
    Route::post('/admin/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::get('/admin/categories/{slug}/edit', [CategoryController::class, 'edit'])->name('categories.edit');
    Route::put('/admin/categories/{slug}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/admin/categories/{slug}', [CategoryController::class, 'destroy'])->name('categories.destroy');

    // Видео обзоры
    Route::post('/admin/video-reviews/store', [VideoReviewsController::class, 'store'])->name('video-reviews.store');
    Route::put('/admin/video-reviews/update/{id}', [VideoReviewsController::class, 'update'])->name('video-reviews.update');
    Route::delete('/admin/video-reviews/destroy/{id}', [VideoReviewsController::class, 'destroy'])->name('video-reviews.destroy');

    Route::get('/category/{slug}/edit', [WorkExampleController::class, 'edit'])->name('category.edit');
    Route::post('/work-examples', [WorkExampleController::class, 'store'])->name('workExamples.store');
    Route::post('/work-examples/{id}', [WorkExampleController::class, 'update'])->name('workExamples.update');
    Route::delete('/work-examples/{id}', [WorkExampleController::class, 'destroy'])->name('workExamples.destroy');

    Route::post('/categories/{slug}/edit-seo', [CategoryController::class, 'updateSeoSection'])->name('category.update.seo');


    Route::get('/categories/{slug}/questions', [FaqController::class, 'index'])->name('category.questions');
    Route::post('/categories/{slug}/questions', [FaqController::class, 'store'])->name('questions.store');
    Route::put('/categories/{slug}/questions/{id}', [FaqController::class, 'update'])->name('questions.update');
    Route::delete('/categories/{slug}/questions/{id}', [FaqController::class, 'destroy'])->name('questions.destroy');

    Route::post('/admin/categories/{slug}/update-visibility', [CategoryController::class, 'updateVisibility'])->name('category.update.visibility');

    Route::post('/admin/categories/{category_slug}/{subcategory_slug}/update-template-variant', [SubcategoryController::class, 'updateTemplateVariant'])->name('subcategory.update.template');

    // Маршруты для редактирования подкатегорий
    Route::get('/admin/subcategories/create', [SubcategoryController::class, 'create'])->name('subcategories.create');
    Route::post('/admin/subcategories/store', [SubcategoryController::class, 'store'])->name('subcategories.store');
    Route::get('/admin/categories/{category_slug}/{subcategory_slug}/edit', [SubcategoryController::class, 'edit'])->name('subcategories.edit');
    Route::put('/admin/categories/{category_slug}/{subcategory_slug}', [SubcategoryController::class, 'update'])->name('subcategories.update');
    Route::delete('/admin/categories/{category_slug}/{subcategory_slug}', [SubcategoryController::class, 'destroy'])->name('subcategories.destroy');
    Route::post('/admin/subcategory-seo/upload-image', [SubcategoryController::class, 'uploadSeoImage'])->name('subcategory.seo.upload_image');
    Route::post('/admin/categories/{category_slug}/{subcategory_slug}/edit-seo', [SubcategoryController::class, 'updateSeoSection'])->name('subcategory.update.seo');
    Route::post('/admin/categories/{category_slug}/{subcategory_slug}/edit-faq', [SubcategoryController::class, 'updateFaqSection'])->name('subcategory.update.faq');
    Route::post('/admin/categories/{category_slug}/{subcategory_slug}/installation-types', [SubcategoryController::class, 'storeInstallationType'])->name('subcategory.installation_types.store');
    Route::post('/admin/categories/{category_slug}/{subcategory_slug}/installation-types/{id}', [SubcategoryController::class, 'updateInstallationType'])->name('subcategory.installation_types.update');
    Route::delete('/admin/categories/{category_slug}/{subcategory_slug}/installation-types/{id}', [SubcategoryController::class, 'destroyInstallationType'])->name('subcategory.installation_types.destroy');

    // Plumbing calculator
    Route::post('/admin/categories/{category_slug}/{subcategory_slug}/plumbing-calc/save', [SubcategoryController::class, 'savePlumbingCalc'])->name('subcategory.plumbing.save_calc');
    Route::post('/admin/categories/{category_slug}/{subcategory_slug}/plumbing-calc/upload-images', [SubcategoryController::class, 'uploadPlumbingCalcImages'])->name('subcategory.plumbing.upload_calc_images');

    // CRUD типов установки для категорий (рольставни и др.)
    Route::get('/admin/categories/{slug}/installation-types', [CategoryController::class, 'installationTypes'])->name('category.installation_types.index');
    Route::post('/admin/categories/{slug}/installation-types', [CategoryController::class, 'storeInstallationType'])->name('category.installation_types.store');
    Route::post('/admin/categories/{slug}/installation-types/{id}', [CategoryController::class, 'updateInstallationType'])->name('category.installation_types.update');
    Route::delete('/admin/categories/{slug}/installation-types/{id}', [CategoryController::class, 'destroyInstallationType'])->name('category.installation_types.destroy');

    // CRUD систем управления рольставнями
    Route::post('/admin/categories/{slug}/roller-shutter-systems', [CategoryController::class, 'storeRollerShutterSystem'])->name('category.roller_shutter_systems.store');
    Route::post('/admin/categories/{slug}/roller-shutter-systems/{id}', [CategoryController::class, 'updateRollerShutterSystem'])->name('category.roller_shutter_systems.update');
    Route::delete('/admin/categories/{slug}/roller-shutter-systems/{id}', [CategoryController::class, 'destroyRollerShutterSystem'])->name('category.roller_shutter_systems.destroy');
    Route::get('/admin/categories/{category_slug}/{subcategory_slug}/work-examples', [SubcategoryController::class, 'getWorkExamples'])->name('subcategory.work-examples.get');

    Route::post('/admin/categories/{category_slug}/{subcategory_slug}/update-visibility', [SubcategoryController::class, 'updateVisibility'])->name('subcategory.update.visibility');


    // Товары
    Route::get('/admin/product/create', [ProductController::class, 'create'])->name('product.create');
    Route::get('/admin/product/edit', [ProductController::class, 'edit'])->name('product.edit');
    Route::post('/admin/product/store', [ProductController::class, 'store'])->name('product.store');
    Route::get('/admin/product/{product_slug}/confirm', [ProductController::class, 'index'])->name('product.index');
    Route::delete('/admin/product/{product_slug}/delete', [ProductController::class, 'destroy'])->name('product.destroy');
    Route::post('/admin/product/{product_slug}/update', [ProductController::class, 'update'])->name('product.update');
    Route::post('/admin/product/{product_slug}/update-photos', [ProductController::class, 'updatePhotos'])->name('product.update.photos');
    Route::get('/admin/product-thumbnails', [ProductController::class, 'thumbnailsPage'])->name('admin.product_thumbnails.page');
    Route::post('/admin/product-thumbnails/process', [ProductController::class, 'thumbnailsProcess'])->name('admin.product_thumbnails.process');
    Route::post('/admin/product/{product_slug}/update-visibility', [ProductController::class, 'updateVisibility'])->name('product.update.visibility');

    Route::post('/admin/product/{product_slug}/update-calc-parametrs', [ProductController::class, 'updateCalcParametrs'])->name('product.update.calc');
Route::post('/admin/product/{id}/update-rolshveni-params', [ProductController::class, 'updateRolshveniParams'])->name('product.update.rolshveni');

    Route::get('/get-model-image/{modelId}', [ProductController::class, 'getModelImage'])->name('get.model.image');

    Route::post('/save-product-image', [ProductController::class, 'saveProductImage'])->name('save.product.image');

    // Добавить в web.php
    Route::get('/get-model-image/{modelId}', [ProductController::class, 'getModelImage'])->name('get.model.image');

    Route::post('/save-product-image/{id}', [ProductController::class, 'saveProductImage']);


    // Табы
    Route::post('/admin/tabs/{product_slug}', [TabsController::class, 'store']);

    Route::put('/admin/tabs/{id}', [TabsController::class, 'update']);
    Route::delete('/admin/tabs/{id}', [TabsController::class, 'destroy']);

    // Сео секция в товаре
    Route::post('/admin/product/{product_slug}/edit-seo', [ProductController::class, 'updateSeoSection'])->name('prodseo.update');


    // Маршруты для редактирования категорий
    Route::get('/admin/models/', [ModelsController::class, 'index'])->name('model.index');
    Route::get('/admin/models/create', [ModelsController::class, 'create'])->name('model.create');
    Route::put('/admin/models/store', [ModelsController::class, 'store'])->name('model.store');
    Route::get('/admin/models/edit/{id}', [ModelsController::class, 'edit'])->name('model.edit');
    Route::put('/admin/models/update/{id}', [ModelsController::class, 'update'])->name('model.update');
    Route::delete('/admin/models/destroy/{id}', [ModelsController::class, 'destroy'])->name('model.destroy');


    // Редактирование слайдера на главной

    // Сохранение нового слайда
    Route::post('/admin/sliders', [FirstScreenSliderController::class, 'store'])->name('admin.sliders.store');

    // Обновление слайда
    Route::put('/admin/sliders/{id}', [FirstScreenSliderController::class, 'update'])->name('admin.sliders.update');

    // Удаление слайда
    Route::delete('/admin/sliders/{id}', [FirstScreenSliderController::class, 'destroy'])->name('admin.sliders.destroy');


    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    Route::put('/orders/{id}/status', [AdminController::class, 'updateStatus'])->name('orders.updateStatus');
    Route::delete('/orders/{id}/delete', [AdminController::class, 'destroyOrder'])->name('orders.destroy');

    Route::get('/pages/create', [PageController::class, 'create'])->name('pages.create');
    Route::post('/pages/store', [PageController::class, 'store'])->name('pages.store');
    Route::get('/pages/edit/{page}', [PageController::class, 'edit'])->name('pages.edit');
    Route::put('/pages/{page}', [PageController::class, 'update'])->name('pages.update');
    Route::delete('/pages/{page}', [PageController::class, 'destroy'])->name('pages.destroy'); // изменено
    Route::get('/allpages', [PageController::class, 'show'])->name('pages.show');

    // Список всех тканей
    Route::get('admin/fabrics', [FabrickController::class, 'index'])->name('admin.fabrics.index');

    // Форма создания новой ткани
    Route::get('admin/fabrics/create', [FabrickController::class, 'create'])->name('admin.fabrics.create');

    // Сохранение новой ткани
    Route::post('admin/fabrics', [FabrickController::class, 'store'])->name('admin.fabrics.store');

    // Просмотр одной ткани (опционально)
    Route::get('admin/fabrics/{fabric}', [FabrickController::class, 'show'])->name('admin.fabrics.show');

    // Форма редактирования ткани
    Route::get('admin/fabrics/{fabric}/edit', [FabrickController::class, 'edit'])->name('admin.fabrics.edit');

    // Обновление данных ткани
    Route::put('admin/fabrics/{fabric}', [FabrickController::class, 'update'])->name('admin.fabrics.update');

    // Удаление ткани
    Route::delete('admin/fabrics/{id}', [FabrickController::class, 'destroy'])->name('admin.fabrics.destroy');

    // Шапка
    Route::get('/header-info/edit', [HeaderInfo::class, 'edit'])->name('admin.header_info.edit');
    Route::post('/header-info/update', [HeaderInfo::class, 'update'])->name('admin.header_info.update');

    // Сохранение нового слайда
    Route::post('/admin/first-screen-sliders/save', [SliderController::class, 'store'])->name('admin.first_screen_sliders.store');

    // Обновление слайда
    Route::put('/admin/first-screen-sliders/{id}', [SliderController::class, 'update'])->name('admin.first_screen_sliders.update');

    // Удаление слайда
    Route::delete('/admin/first-screen-sliders/destroy/{id}', [SliderController::class, 'destroy'])->name('admin.first_screen_sliders.destroy');

    // Генератор товаров
    Route::get('/admin/prodgenerator/index', [ProductGenerator::class, 'index'])->name('admin.prod_generator.index');
    Route::put('/admin/prodgenerator/generate', [ProductGenerator::class, 'create'])->name('admin.prod_generator.create');

    // Пакетный пересчет минимальной цены
    Route::get('/admin/prices/min', [MinPriceRunController::class, 'page'])->name('admin.prices.min');
    Route::post('/admin/prices/min/start', [MinPriceRunController::class, 'start'])->name('admin.prices.min.start');
    Route::post('/admin/prices/min/next', [MinPriceRunController::class, 'next'])->name('admin.prices.min.next');
    Route::post('/admin/prices/min/pause', [MinPriceRunController::class, 'pause'])->name('admin.prices.min.pause');
    Route::post('/admin/prices/min/resume', [MinPriceRunController::class, 'resume'])->name('admin.prices.min.resume');
    Route::post('/admin/prices/min/stop', [MinPriceRunController::class, 'stop'])->name('admin.prices.min.stop');
    Route::get('/admin/prices/min/state', [MinPriceRunController::class, 'state'])->name('admin.prices.min.state');
    Route::get('/admin/prices/min/runs', [MinPriceRunController::class, 'runs'])->name('admin.prices.min.runs');
    Route::get('/admin/prices/min/results', [MinPriceRunController::class, 'results'])->name('admin.prices.min.results');
    Route::get('/admin/prices/min/results/export', [MinPriceRunController::class, 'export'])->name('admin.prices.min.export');
    Route::get('/admin/prices/min/sizes/preview', [MinPriceRunController::class, 'sizesPreview'])->name('admin.prices.min.sizes.preview');
    Route::post('/admin/prices/min/sizes/update', [MinPriceRunController::class, 'sizesUpdate'])->name('admin.prices.min.sizes.update');

    Route::get('/colors', [ColorController::class, 'index']);

    // Route::post('/admin/pages/upload-image', function (Request $request) {
    //     if ($request->hasFile('image')) {
    //         $path = $request->file('image')->store('public/pages');
    //         $url = Storage::url($path); // Генерируем URL
    
    //         return response()->json(['url' => $url]);
    //     }
    
    //     return response()->json(['error' => 'Ошибка загрузки'], 400);
    // });

    Route::post('/admin/pages/upload-image', [PageController::class, 'uploadImage']);
    Route::get('/admin/add-img-to-prods-script', [addImgFromGeneration::class, 'addImgFromGeneration']);


});


Route::get('/', [HomeController::class, 'index'])->name('front.home');
Route::get('/checkout', [CheckoutController::class, 'index']);
Route::get('/cart', [CartController::class, 'show'])->name('cart.show');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');


Route::get('/products/{categoryId}', [HomeController::class, 'getProductsByCategory']);

// Добавление товара в корзину через URL
// Route::get('/cart/add/{productId}', [CartController::class, 'addToCart'])->name('cart.add');
// Route::post('/cart/add', [CartController::class, 'addToCart'])->name('cart.add');
Route::middleware('web')->group(function () {
    Route::post('/cart/add', [CartController::class, 'addToCart'])->name('cart.add');
});
Route::post('/cart/remove', [CartController::class, 'removeFromCart'])->name('cart.remove');
Route::get('/cart/edit/{key}', [CartController::class, 'getCartItem'])->name('cart.edit');
Route::post('/cart/update', [CartController::class, 'updateCartItem'])->name('cart.update');

Route::post('/cart/update-delivery', [CartController::class, 'updateDelivery'])->name('cart.updateDelivery');



// Общие маршруты должны идти после более специфичных маршрутов
Route::get('/{category_slug}/{subcategory_slug}/{product_slug}', [ProductController::class, 'show'])->name('product.show');


Route::get('/{category_slug}/{subcategory_slug}', [SubcategoryController::class, 'show'])->name('subcategory.show')->middleware('slashes');
Route::get('/{slug}', [CategoryController::class, 'show'])->name('category.show')->middleware('slashes');



Route::post('/filter-cat-products/{id}', [CategoryController::class, 'filterProducts'])->name('cat.filter');
Route::post('/filter-subcat-products/{id}', [SubcategoryController::class, 'filterProducts'])->name('subcat.filter');

Route::get('/api/models', [ProductController::class, 'getModelsBySubcategory']);
// Заказ
Route::post('/create-order', [OrderController::class, 'create'])->name('createOrder');


Route::get('/sitemap.xml', function () {
    return response()->file(public_path('sitemap.xml'));
});
Route::post('/send-form', [FormController::class, 'send'])->name('form.send');

// Route::post('/filter-subcat-products', [SubcategoryController::class, 'filterProducts'])->name('subcat.filter');


// php artisan cache:clear
// php artisan config:clear
// php artisan view:clear
