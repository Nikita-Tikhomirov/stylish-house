<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Subcategory;
use App\Models\Category;
use Illuminate\Support\Str;
use App\Models\ProdModel;
use App\Models\Tab;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Jobs\CleanCategoryDuplicates;

class ProductGenerator extends Controller
{
    public function index()
    {

        $subcategories = Subcategory::all();
        $models = ProdModel::all();
        return view('admin.prodgenerator', compact('subcategories', 'models'));
    }

    public function create(Request $request)
    {
        // Массив цветов

        // $colors = [
        //     '#9894A1' => 'Авиано бежевый',
        //     '#D5D9E4' => 'Авиано белый',
        // ];

        // $colors = array_map(fn($name) => preg_replace('/^\S+\s+/u', '', $name), $colors);


        // $clothAndMatherial = [
        //     "0 категория" => ["Аскона"],
        //     "1 категория" => ["Лён", "Базель", "Базель перл", "Аида", "Краш", "Того флай", "Того сан", "Того флаур", "Того шипс", "Дорио", "Джела", "Сальва", "Васто",],
        //     "2 категория" => ["Корона", "Монтелла", "Шайн", "Аида перл", "Краш перл",],
        //     "3 категория" => ["Авиано", "Бароло перл", "Тивали", "Димаро",],
        //     "4 категория" => ["Аляска перл", "Флаер перл", "Гарда", "Атри", "Флаер", "Гарда перл", "Скио перл", "Краш металлик", "Палма (дуэт)",],
        //     "5 категория" => ["Комо", "Корсика", "Сутрио", "Новелла", "Самоа", "Сето блэкаут", "Мун блэкаут", "Силкшейд алю", "Винчи", "Палма блэкаут (дуэт)",],
        //     "6 категория" => ["Леванто (дуэт)", "Оттава (дуэт)", "Тренто (дуэт)", "Капри (дуэт)",]
        // ];

        // set_time_limit(240);

        //  !!!!!!!  Для одной модели  !!!!!!!

        // $model = ProdModel::find($request->input('model'));
        // $modelTitle = $model->title;
        // $subcategoryId = $request->input('subcategory_id');
        // $prodTitle = $request->input('title');

        // $firstscreentext = $request->input('firstscreentext');
        // $seo = $request->input('seotext');
        // $characteristic = $request->input('characteristic');

        // $meataTitle = $request->input('titlemeta');
        // $meataDescription = $request->input('descriptionmeta');

        // $generatedTitles = [];

        // foreach ($clothAndMatherial as $category => $materials) {
        //     foreach ($materials as $material) {
        //         foreach ($colors as $hex => $colorName) {
        //             if (str_contains($colorName, $material)) {
        //                 $fullTitle = $prodTitle . ' ' . $material . ' ' . str_replace($material, '', $colorName);

        //                 if (in_array($fullTitle, $generatedTitles)) {
        //                     continue;
        //                 }
        //                 $generatedTitles[] = $fullTitle;

        //                 $baseSlug = Str::slug($fullTitle);
        //                 $slug = $baseSlug;
        //                 $count = 1;

        //                 while (Product::where('slug', $slug)->exists()) {
        //                     $slug = $baseSlug . '-' . $count;
        //                     $count++;
        //                 }

        //                 $metaTitleFinal = str_replace('[название товара]', $fullTitle, $meataTitle);
        //                 $metaDescriptionFinal = str_replace('[название товара]', $fullTitle, $meataDescription);
        //                 $firstScreenTextFinal = str_replace('[название товара]', $fullTitle, $firstscreentext);
        //                 $seoFinal = str_replace('[название товара]', $fullTitle, $seo);

        //                 Product::create([
        //                     'slug' => $slug,
        //                     'title' => $metaTitleFinal,
        //                     'description' => $metaDescriptionFinal,
        //                     'h1' => $fullTitle,
        //                     'category_id' => 14,
        //                     'subcategory_id' => $subcategoryId,
        //                     'color' => $hex,
        //                     'image_path' => null,
        //                     'model_id' => $request->input('model'),
        //                     'cloth' => $category,
        //                     'material' => $material,
        //                     'first_screenn_description' => $firstScreenTextFinal,
        //                     'seo' => $seoFinal,
        //                     'related_product_ids' => $relatedProducts ?? [],
        //                     'alternative_product_ids' => $alternativeProducts ?? [],
        //                     'characteristic' => $characteristic,
        //                 ]);
        //             }
        //         }
        //     }
        // }

        // $subcategories = Subcategory::all();
        // $models = ProdModel::all();
        // return view('admin.prodgenerator', compact('subcategories', 'models'));


        // !!!!!!! Для жалюзи !!!!!!!

        // set_time_limit(240);

        //     $model = ProdModel::find($request->input('model'));
        //     $subcategoryId = $request->input('subcategory_id');
        //     $prodTitle = $request->input('title');

        //     $firstscreentext = $request->input('firstscreentext');
        //     // $seo = $request->input('seotext');
        //     $characteristic = $request->input('characteristic');

        //     $meataTitle = $request->input('titlemeta');
        //     $meataDescription = $request->input('descriptionmeta');

        //     $generatedTitles = [];

        //     foreach ($colors as $hex => $colorName) {
        //         $fullTitle = $prodTitle . ' ' . $colorName;

        //         if (in_array($fullTitle, $generatedTitles)) {
        //             continue;
        //         }
        //         $generatedTitles[] = $fullTitle;

        //         $baseSlug = Str::slug($fullTitle);
        //         $slug = $baseSlug;
        //         $count = 1;

        //         while (Product::where('slug', $slug)->exists()) {
        //             $slug = $baseSlug . '-' . $count;
        //             $count++;
        //         }

        //         $metaTitleFinal = str_replace('[название товара]', $fullTitle, $meataTitle);
        //         $metaDescriptionFinal = str_replace('[название товара]', $fullTitle, $meataDescription);
        //         $firstScreenTextFinal = str_replace('[название товара]', $fullTitle, $firstscreentext);
        //         // $seoFinal = str_replace('[название товара]', $fullTitle, $seo);

        //         $product = Product::create([
        //             'slug' => $slug,
        //             'title' => $metaTitleFinal,
        //             'description' => $metaDescriptionFinal,
        //             'h1' => $fullTitle,
        //             'category_id' => 1,
        //             'subcategory_id' => $subcategoryId,
        //             'color' => $hex,
        //             'image_path' => null,
        //             'model_id' => $request->input('model'),
        //             'cloth' => null,
        //             'material' => null,
        //             'first_screenn_description' => $firstScreenTextFinal,
        //             // 'seo' => $seoFinal,
        //             'related_product_ids' => $relatedProducts ?? [],
        //             'alternative_product_ids' => $alternativeProducts ?? [],
        //             'characteristic' => $characteristic,
        //         ]);

        //         for ($i = 1; $i <= 20; $i++) {
        //             $tabContent = $request->input("tab{$i}");
        //             $tabTitle = $request->input("tab{$i}title") ?? "Таб $i";

        //             if (!empty($tabContent)) {
        //                 Tab::create([
        //                     'title' => $tabTitle,
        //                     'tab' => str_replace('[название товара]', $fullTitle, $tabContent),
        //                     'product_id' => $product->id,
        //                 ]);
        //             }
        //         }
        //     }


        //     $subcategories = Subcategory::all();
        //     $models = ProdModel::all();
        //     return view('admin.prodgenerator', compact('subcategories', 'models'));


        // Для массива моделей 

        //     $modelIdsArr = range(80, 106);

        //     $subcategoryId = $request->input('subcategory_id');
        //     // $prodTitle = $request->input('title');

        //     $firstscreentext = $request->input('firstscreentext');
        //     $seo = $request->input('seotext');
        //     $characteristic = $request->input('characteristic');

        //     $meataTitle = $request->input('titlemeta');
        //     $meataDescription = $request->input('descriptionmeta');

        //     $generatedTitles = [];

        //     foreach ($modelIdsArr as $modelId) {
        //         $model = ProdModel::find($modelId);
        //         if (!$model) {
        //             continue;
        //         }

        //         foreach ($clothAndMatherial as $category => $materials) {
        //             foreach ($materials as $material) {
        //                 foreach ($colors as $hex => $colorName) {
        //                     if (str_contains($colorName, $material)) {
        //                         $fullTitle = $model->h1 . ' ' . $material . ' ' . str_replace($material, '', $colorName);

        //                         if (in_array($fullTitle, $generatedTitles)) {
        //                             continue;
        //                         }
        //                         $generatedTitles[] = $fullTitle;

        //                         $baseSlug = Str::slug($fullTitle);
        //                         $slug = $baseSlug;
        //                         $count = 1;

        //                         while (Product::where('slug', $slug)->exists()) {
        //                             $slug = $baseSlug . '-' . $count;
        //                             $count++;
        //                         }

        //                         $metaTitleFinal = str_replace('[название товара]', $fullTitle, $meataTitle);
        //                         $metaDescriptionFinal = str_replace('[название товара]', $fullTitle, $meataDescription);
        //                         $firstScreenTextFinal = str_replace('[название товара]', $fullTitle, $firstscreentext);
        //                         $seoFinal = str_replace('[название товара]', $fullTitle, $seo);

        //                         $product = Product::create([
        //                             'slug' => $slug,
        //                             'title' => $metaTitleFinal,
        //                             'description' => $metaDescriptionFinal,
        //                             'h1' => $fullTitle,
        //                             'category_id' => 14,
        //                             'subcategory_id' => $subcategoryId,
        //                             'color' => $hex,
        //                             'image_path' => null,
        //                             'model_id' => $modelId,
        //                             'cloth' => $category,
        //                             'material' => $material,
        //                             'first_screenn_description' => $firstScreenTextFinal,
        //                             'seo' => $seoFinal,
        //                             'related_product_ids' => $relatedProducts ?? [],
        //                             'alternative_product_ids' => $alternativeProducts ?? [],
        //                             'characteristic' => $characteristic,
        //                         ]);

        //                         // 👉 Генерация табов
        //                         for ($i = 1; $i <= 20; $i++) {
        //                             $tabContent = $request->input("tab{$i}");
        //                             $tabTitle = $request->input("tab{$i}title") ?? "Таб $i";

        //                             if (!empty($tabContent)) {
        //                                 Tab::create([
        //                                     'title' => $tabTitle,
        //                                     'tab' => str_replace('[название товара]', $fullTitle, $tabContent),
        //                                     'product_id' => $product->id,
        //                                 ]);
        //                             }
        //                         }
        //                     }
        //                 }
        //             }
        //         }
        //     }

        // $subcategories = Subcategory::all();
        // $models = ProdModel::all();
        // return view('admin.prodgenerator', compact('subcategories', 'models'));


        // !!!!! Добавление табов в имеющиеся товары. !!!!!!

        // !!!!!!  Рулонные шторы  !!!!!!!

        // $modelIdsArr = range(33, 48);

        // foreach ($modelIdsArr as $modelId) {
        //     // Получаем все товары для текущей модели
        //     $products = Product::where('model_id', $modelId)->get();

        //     foreach ($products as $product) {
        //         $fullTitle = $product->h1; // Получаем h1 товара

        //         // Генерация табов
        //         for ($i = 1; $i <= 20; $i++) {
        //             $tabContent = $request->input("tab{$i}");
        //             $tabTitle = $request->input("tab{$i}title") ?? "Таб $i";

        //             if (!empty($tabContent)) {
        //                 Tab::create([
        //                     'title' => $tabTitle,
        //                     'tab' => str_replace('[название товара]', $fullTitle, $tabContent),
        //                     'product_id' => $product->id,
        //                 ]);
        //             }
        //         }
        //     }
        // }

        // return response()->json(['message' => 'Табы успешно созданы']);

        // !!!!!! Жалюзи !!!!!!

        // $modelIdsArr = range(60, 77);

        // foreach ($modelIdsArr as $modelId) {
        //     // Получаем все товары для текущей модели
        //     $products = Product::where('model_id', $modelId)->get();

        //     foreach ($products as $product) {
        //         $fullTitle = $product->h1;
        //         $subcategoryTitle = $product->subcategory->titleh1 ?? '';
        //         $combinedTitle = trim($subcategoryTitle . ' ' . $fullTitle); // "Категория Товар"




        //         // Генерация табов
        //         for ($i = 1; $i <= 20; $i++) {
        //             $tabContent = $request->input("tab{$i}");
        //             $tabTitle = $request->input("tab{$i}title") ?? "Таб $i";

        //             if (!empty($tabContent)) {
        //                 // Замена двух плейсхолдеров
        //                 $replacedContent = str_replace('[Название категории + название товара]', $combinedTitle, $tabContent);

        //                 Tab::create([
        //                     'title' => $tabTitle,
        //                     'tab' => $replacedContent,
        //                     'product_id' => $product->id,
        //                 ]);
        //             }
        //         }
        //     }
        // }

        // return response()->json(['message' => 'Табы успешно созданы']);


        // !!!! Очистка табов !!!!!!!!


        // $modelIdsArr = range(60, 77);

        // // Получаем все товары по нужным model_id
        // $products = Product::whereIn('model_id', $modelIdsArr)->get();

        // foreach ($products as $product) {
        //     // Удаляем все табы, связанные с этим товаром
        //     Tab::where('product_id', $product->id)->delete();
        // }

        // return response()->json(['message' => 'Все табы успешно удалены для выбранных моделей']);


        // !!!!! Добавление фото ткани к товарам !!!!! 
        // $modelIdsArr = range(60, 61);

        // $fabricDir = 'public/materials/jaluzi'; // папка с фото
        // $publicUrlBase = '/storage/materials/jaluzi/';

        // // Функция для нормализации строк: нижний регистр + убираем лишние пробелы
        // function normalize($string) {
        //     $string = mb_strtolower(trim(preg_replace('/\s+/', ' ', $string)));
        //     $string = preg_replace('/[^\p{L}\p{N}\s]+/u', '', $string); // удаляем все кроме букв, цифр и пробелов
        //     return $string;
        // }


        // // Получаем список всех файлов тканей
        // $photoFiles = collect(Storage::files($fabricDir))
        //     ->filter(fn($file) => preg_match('/\.(jpe?g|png|webp)$/i', $file))
        //     ->mapWithKeys(function ($filePath) {
        //         $filename = pathinfo($filePath, PATHINFO_FILENAME);
        //         return [normalize($filename) => $filePath];
        //     });

        // $products = Product::whereIn('model_id', $modelIdsArr)->get();
        // $updated = 0;

        // foreach ($products as $product) {
        //     $h1 = normalize($product->h1);

        //     foreach ($photoFiles as $normalizedFilename => $filePath) {
        //         if (mb_strpos($h1, $normalizedFilename) !== false) {
        //             $product->fabric_photo = $publicUrlBase . basename($filePath);
        //             $product->save();
        //             $updated++;
        //             break; // Один файл на один товар
        //         }
        //     }
        // }

        // return response()->json([
        //     'message' => "Готово. Привязано фото к $updated товарам"
        // ]);


        // Записать категорию в товар по id 


        // $modelIdsArr = range(61, 61);

        // Получаем все товары по нужным model_id
        // $products = Product::whereIn('model_id', [68,69,70,71])->get();

        // foreach ($products as $product) {
        //     $product->min_width = 450;
        //     $product->min_height = 600;

        //     $product->save();
        // }

        // return response()->json([
        //     'message' => "Готово."
        // ]);


        // $modelIdsArr = range(33, 33);
        // $products = Product::whereIn('model_id', $modelIdsArr)->get();

        // foreach ($products as $product) {
        //    $prodH1 = $product->h1;
        //     // Дальше идет логика. Переписываем три поля в товаре. 
        //     $product->title = $titleText = 'Рулонные шторы '.$prodH1.' | Купить рулонные шторы '.$prodH1.' по отличной цене в специализированном интернет-магазине';
        //     $product->description = 'Рулонные шторы '.$prodH1.' – широкий ассортимент в специализированном интернет-магазине. Отличные цены, стильный дизайн и быстрая доставка. Выбирайте лучшие рулонные шторы для вашего интерьера!';
        //     $product->first_screenn_description = 'Рулонные шторы '.$prodH1.' – это современное решение для оформления окон, которое сочетает в себе стильный внешний вид, практичность и доступную цену. В нашем специализированном интернет-магазине вы найдете широкий выбор моделей, подходящих для любого интерьера: от лаконичных однотонных до ярких дизайнерских вариантов';
        //     $product->save();
        // }
        // $modelIdsArr = range(80, 106);
        // $products = Product::with('tabs')->whereIn('model_id', $modelIdsArr)->get();

        // $tabsToUpdate = [];

        // foreach ($products as $product) {
        //     $prodH1 = $product->h1;


        //     $descriptionTab = $product->tabs->firstWhere('title', 'Описание');

        //     if ($descriptionTab) {
        //         // Формируем новый текст с заменой [название товара] на $prodH1
        //         $newDescription = "<h2>Современное решение для пластиковых окон</h2>
        //         <p>Рулонные шторы плиссе $prodH1 — это современное, функциональное и эстетичное решение для оформления пластиковых окон. Их отличительная особенность — складчатая форма, напоминающая «гармошку», которая позволяет компактно собирать ткань и точно регулировать поток света. Такие шторы идеально подходят как для жилых, так и для офисных помещений, обеспечивая комфорт и визуальную легкость.</p>
        //         <h2>Преимущества рулонных штор плиссе</h2>
        //         <ul>
        //         <li>Максимальная защита от солнечного света благодаря плотному прилеганию к стеклу и возможности установки на каждую створку.</li>
        //         <li>Компактность и экономия пространства — не закрывают подоконник и не мешают открытию окон.</li>
        //         <li>Универсальность установки: монтаж в проём или на створку без сверления.</li>
        //         <li>Широкий выбор материалов — от прозрачных до блэкаут.</li>
        //         <li>Простота ухода: ткани обработаны антистатическими и пылеотталкивающими составами.</li>
        //         </ul>

        //         <h2>Где используются шторы плиссе</h2>
        //         <p>Идеальны для кухни, спальни, ванной комнаты и офисов. Особенно подходят для нестандартных окон: мансардных, арочных и других необычных форм.</p>

        //         <h2>Разновидности и дизайн</h2>
        //         <ul>
        //         <li>Однослойные шторы — легкие и пропускают мягкий свет.</li>
        //         <li>Двухслойные модели «день-ночь» для регулировки освещенности.</li>
        //         <li>Типы управления: ручное, цепочное, пружинное.</li>
        //         <li>Цветовая палитра от нейтральных до ярких оттенков, подходит под любой интерьер.</li>
        //         </ul>

        //         <h2>Почему выбирают нас</h2>
        //         <ul>
        //         <li>Широкий ассортимент рулонных штор плиссе [название товара] с гарантией качества.</li>
        //         <li>Подбор модели под интерьер и оперативная доставка по Москве и МО.</li>
        //         <li>Помощь с установкой и работа напрямую с производителями без наценок.</li>
        //         </ul>

        //         <h2>Как заказать</h2>
        //         <p>Оформите заказ онлайн в пару кликов и получите стильные, удобные и долговечные шторы плиссе, которые подчеркнут ваш интерьер. Наши специалисты всегда готовы помочь с выбором и ответить на вопросы.</p>
        //         ";

        //         // Сохраним изменения в массив для пакетного обновления
        //         $tabsToUpdate[] = [
        //             'id' => $descriptionTab->id,
        //             'tab' => $newDescription,
        //         ];
        //     }
        // }

        // Выполняем пакетное обновление


        // foreach ($tabsToUpdate as $data) {
        //     $descriptionTab = Tab::find($data['id']);
        //     $descriptionTab->tab = $data['tab'];
        //     $descriptionTab->save();
        // }



        // $modelIdsArr = array_diff(range(80, 106), [81, 87, 91]);

        // $products = Product::with('tabs')->whereIn('model_id', $modelIdsArr)->get();

        // foreach ($products as $product) {
        //     $product->delete();
        // }



        // $product = Product::where('id', '294')->first();
        // dd($product->color);
        // return response()->json([
        //     'message' => "Готово."
        // ]);
        //     $badProducts = Product::query()
        //         ->where('model_id', 63)
        //         ->where('subcategory_id', 7)
        //         ->whereNotNull('fabric_photo')
        //         ->where('fabric_photo', '<>', '')
        //         ->whereRaw("SUBSTRING_INDEX(fabric_photo, '/', -1) LIKE '25-%'")
        //         ->get();

        //     $tabsToUpdate = [];

        //     foreach ($badProducts as $product) {
        //         $descriptionTab = $product->tabs->firstWhere('title', 'Описание');
        //         if ($descriptionTab) {
        //             $newDescription = '<h2>Современные жалюзи для вашего интерьера</h2>
        //                 <p>Горизонтальные жалюзи '.$product->h1.' — практичное решение для оформления окон в жилых и коммерческих помещениях. Они обеспечивают надёжную защиту от солнечного света, удобное регулирование освещённости и аккуратный внешний вид.</p>
        //                 <p>Конструкция состоит из ламелей, которые позволяют точно контролировать свет и приватность. Жалюзи крепятся на проём, створку или потолок. Управление — цепочкой, шнуром, пружиной или автоматикой.</p>

        //                 <h2>Основные преимущества</h2>
        //                 <ul>
        //                 <li>Защита от света и приватность</li>
        //                 <li>Подходят для любых окон</li>
        //                 <li>Разнообразие материалов и дизайнов</li>
        //                 <li>Простой уход и установка</li>
        //                 <li>Минималистичный стиль, не загромождающий пространство</li>
        //                 </ul>

        //                 <h2>Варианты исполнения</h2>
        //                 <ul>
        //                 <li>Горизонтальные</li>
        //                 <li>Вертикальные</li>
        //                 <li>Рулонные и кассетные</li>
        //                 <li>Мультифактурные модели</li>
        //                 </ul>

        //                 <h2>Где используют жалюзи</h2>
        //                 <ul>
        //                 <li>Кухня и ванные — устойчивы к влаге и загрязнениям</li>
        //                 <li>Спальня и гостиная — создают уют и комфорт</li>
        //                 <li>Офис и кабинет — подчёркивают стиль и помогают сосредоточиться</li>
        //                 <li>Балконы и нестандартные окна — подходят для любых форм</li>
        //                 </ul>

        //                 <h2>Почему выбирают нас</h2>
        //                 <p>В нашем каталоге большой выбор жалюзи с гарантией качества, по выгодным ценам. Предлагаем консультации, быструю доставку по Москве и области, а также профессиональную помощь с подбором и установкой.</p>

        //                 <p>Оформите заказ прямо сейчас и обновите интерьер легко и стильно!</p>';
        //         };
        //         $tabsToUpdate[] = [
        //             'id' => $descriptionTab->id,
        //             'tab' => $newDescription,
        //         ];

        //         foreach ($tabsToUpdate as $data) {
        //             $descriptionTab = Tab::find($data['id']);
        //             $descriptionTab->tab = $data['tab'];
        //             $descriptionTab->save();
        //         };
        //         // $product->save();
        //         // dd($product->model_id);
        //     }

        //     return response()->json([
        //         'message' => "Готово."
        //     ]);

        // Проверка на дубли
        // $categoryId = 1; 
        // Получаем все продукты в указанной категории
        // $products = Product::where('category_id', $categoryId)->get();

        // $slugs = [];

        // foreach ($products as $product) {
        //     // Проверяем, есть ли уже этот slug (без -1)
        //     if (preg_match('/^(.*?)(-1)?$/', $product->slug, $matches)) {
        //         $slugBase = $matches[1];

        //         // Если slug уже есть, удаляем текущий продукт
        //         if (isset($slugs[$slugBase])) {
        //             $product->delete();
        //         } else {
        //             // Если slug уникальный, добавляем в массив
        //             $slugs[$slugBase] = $product->id;
        //         }
        //     }
        // }


        // dispatch(new CleanCategoryDuplicates($categoryId));
        // 1. Находим id товаров, которые нужно оставить (по каждому h1 берем минимальный id)
        // $keepIds = DB::table('products')
        //     ->select(DB::raw('MIN(id) as id'))
        //     ->where('category_id', $categoryId)
        //     ->groupBy('h1')
        //     ->pluck('id');

        // // 2. Удаляем все остальные товары с тем же category_id
        // $deleted = DB::table('products')
        //     ->where('category_id', $categoryId)
        //     ->whereNotIn('id', $keepIds)
        //     ->delete();

        // echo "Удалено дублей: {$deleted}";
        // $message = '';

        // // Получаем все уникальные h1 в категории, обрезая пробелы
        // $uniqueH1s = Product::where('category_id', $categoryId)
        //     ->select(DB::raw('TRIM(h1) AS trimmed_h1'))
        //     ->groupBy('trimmed_h1')
        //     ->pluck('trimmed_h1')
        //     ->toArray();

        // foreach ($uniqueH1s as $h1) {
        //     // Получаем все товары с указанным h1 (после обрезки пробелов)
        //     $products = Product::where('category_id', $categoryId)
        //         ->where(DB::raw('TRIM(h1)'), $h1)
        //         ->get();

        //     if (count($products) > 1) {
        //         $message .= 'Найдено ' . count($products) . ' дубликатов для H1: ' . $h1 . '. ';

        //         // Оставляем первый товар, остальные удаляем
        //         $firstProduct = $products->first();
        //         $productIdsToDelete = $products->slice(1)->pluck('id')->toArray();

        //         Product::whereIn('id', $productIdsToDelete)->delete();

        //         $message .= 'Удалены дубликаты с ID: ' . implode(', ', $productIdsToDelete) . '. ';
        //     } else {
        //         $message .= 'Нет дубликатов для H1: ' . $h1 . '. ';
        //     }
        // }

        // $message .= 'Очистка категории ' . $categoryId . ' завершена! ✨';
        // $products = Product::where('model_id', 78)->get();
        // foreach ($products as $product) {
        //     $product->image_path = null;
        //     $product->save();
        // }
        // $modelIds = [33, 35, 36]; // массив id моделей
        // Product::where('model_id', 91)->update(['model_id' => 107]);
        // $modelIds = range(62, 64);
        // $template = 'Кассетные жалюзи :h1 | Купить в интернет-магазине Stylish-House.net';

        // $products = Product::whereIn('model_id', $modelIds)->get();

        // foreach ($products as $product) {
        //     $title = str_replace(':h1', $product->h1, $template);

        //     // убираем двойные (и более) пробелы
        //     $title = preg_replace('/\s+/', ' ', $title);
        //     $title = trim($title);

        //     // тоже можно очистить h1, если нужно
        //     $product->h1 = preg_replace('/\s+/', ' ', $product->h1);
        //     $product->h1 = trim($product->h1);

        //     $product->title = $title;
        //     $product->save();
        // }
        // $products = Product::all();

        // foreach ($products as $product) {
        //     if (preg_match('/\[(.*?)\]/', $product->description)) {
        //         $product->description = preg_replace('/\[(.*?)\]/', 'Москве', $product->description);
        //         $product->save();
        //     }
        // }

        // $modelIds = [44, 47, 48]; 

        // $products = Product::whereIn('model_id', $modelIds)->get();

        // foreach($products as $product){
        //     $product->delete();
        // }

//         $titlesArr =[
// "[Название товара] — современная кассетная штора для стильного интерьера",
// "Элегантные кассетные жалюзи [Название товара] для окон любого размера",
// "[Название товара] — практичные и удобные кассетные жалюзи для дома и офиса",
// "Кассетные жалюзи [Название товара] — сочетание функциональности и дизайна",
// "[Название товара] — качественные кассетные жалюзи с лёгким монтажом",
// "[Название товара] — кассетные жалюзи для защиты от солнца и посторонних взглядов",
// "Стильные кассетные жалюзи [Название товара] для современных интерьеров",
// "[Название товара] — удобные и надёжные кассетные жалюзи для дома",
// "Кассетные жалюзи [Название товара] — практичное решение для окон любого типа",
// "[Название товара] — элегантные кассетные жалюзи с современным дизайном",
// "Надёжные кассетные жалюзи [Название товара] для спальни, гостиной и офиса",
// "[Название товара] — функциональные кассетные жалюзи с лёгкой установкой",
// "Кассетные жалюзи [Название товара] — комфорт и стиль в одном изделии",
// "[Название товара] — качественные кассетные жалюзи с долговечными материалами",
// "Эстетичные и практичные кассетные жалюзи [Название товара] для дома",
// "[Название товара] — кассетные жалюзи, создающие уют и комфорт в помещении",
// "Универсальные кассетные жалюзи [Название товара] для любых окон",
// "[Название товара] — современное решение для регулировки света и приватности",
// "Кассетные жалюзи [Название товара] — стиль и практичность в каждом элементе",
// "[Название товара] — надёжные кассетные жалюзи для комфортного интерьера",
// "Кассетные жалюзи [Название товара] — элегантное оформление окон любой комнаты",
// "[Название товара] — функциональные и стильные кассетные жалюзи",
// "Современные кассетные жалюзи [Название товара] с лёгким монтажом",
// "[Название товара] — кассетные жалюзи, идеально подходящие для любого интерьера",
// "Практичные и удобные кассетные жалюзи [Название товара] для дома и офиса",
// "[Название товара] — кассетные жалюзи с гармоничным сочетанием дизайна и функциональности",
// "Кассетные жалюзи [Название товара] — защита от солнца с комфортом",
// "[Название товара] — стильные и долговечные кассетные жалюзи",
// "Кассетные жалюзи [Название товара] для современного и уютного интерьера",
// "[Название товара] — надёжные и практичные кассетные жалюзи для любого окна",
// "Элегантные и удобные кассетные жалюзи [Название товара] для дома",
// "[Название товара] — качественные кассетные жалюзи с современным механизмом",
// "Кассетные жалюзи [Название товара] — комфорт и стиль для вашего интерьера",
// "[Название товара] — функциональные кассетные жалюзи с простым монтажом",
// "Универсальные и стильные кассетные жалюзи [Название товара] для всех помещений",
// "[Название товара] — кассетные жалюзи с практичным и современным дизайном",
// "Кассетные жалюзи [Название товара] — удобство и уют в каждом элементе",
// "[Название товара] — надёжные кассетные жалюзи для дома и офиса",
// "Эстетичные кассетные жалюзи [Название товара] с лёгким управлением",
// "[Название товара] — практичные и стильные кассетные жалюзи для любого окна",
// "Кассетные жалюзи [Название товара] — гармония дизайна и функциональности",
// "[Название товара] — кассетные жалюзи для защиты от солнца и уюта в доме",
// "Современные и удобные кассетные жалюзи [Название товара]",
// "[Название товара] — качественные кассетные жалюзи для стильного интерьера",
// "Кассетные жалюзи [Название товара] — элегантное решение для вашего дома",
// "[Название товара] — практичные кассетные жалюзи с долговечными материалами",
// "Надёжные и стильные кассетные жалюзи [Название товара] для любого окна",
// "[Название товара] — функциональные и эстетичные кассетные жалюзи",
// "Кассетные жалюзи [Название товара] — комфорт и уют для любого интерьера",
// "[Название товара] — универсальные и стильные кассетные жалюзи для дома и офиса",
//         ];

//         $descriptionArr =[
// "Стильные кассетные жалюзи [Название товара] подходят для дома и офиса, регулируют освещённость и сохраняют приватность. Разнообразие цветов и тканей позволяет подобрать идеальный вариант под интерьер любой комнаты.",
// "Кассетные жалюзи — удобное решение для окон любого размера. Они легко монтируются, защищают от солнца и создают уютную атмосферу в помещении, сочетая функциональность и современный дизайн.",
// "[Название товара] — практичные кассетные жалюзи, обеспечивающие мягкое рассеянное освещение, комфорт и стильный вид помещения. Лёгкий монтаж и широкий выбор тканей делают их универсальным решением для дома или офиса.",
// "Кассетные жалюзи создают гармонию в интерьере, защищая от солнца и посторонних взглядов. Они подходят для любых окон и легко устанавливаются, обеспечивая уют и комфорт в спальне, гостиной или кабинете.",
// "Эти кассетные жалюзи обеспечивают функциональность и стиль. Они регулируют освещённость, создают уют и комфорт в помещении, легко монтируются и подходят для окон любого размера, гармонично дополняя интерьер.",
// "[Название товара] — кассетные жалюзи с долговечными материалами и современным механизмом подъёма. Они обеспечивают защиту от яркого солнца, комфортное освещение и стильное оформление окна в любом помещении.",
// "Кассетные жалюзи помогают регулировать свет в комнате, создавая уют и комфорт. Простая установка и разнообразие тканей и цветов делают их идеальным решением для любого интерьера и стиля.",
// "Эти жалюзи обеспечивают мягкое освещение, защищают от солнца и посторонних взглядов. Лёгкий монтаж и долговечные материалы делают их практичным и эстетичным решением для дома, офиса или квартиры.",
// "[Название товара] — функциональные и стильные кассетные жалюзи для любого интерьера. Они легко устанавливаются, обеспечивают комфортное освещение и защиту от солнца, придавая помещению уют и завершённость.",
// "Кассетные жалюзи создают уют и комфорт в помещении, защищая от яркого солнца и посторонних взглядов. Они легко монтируются, подходят для окон любого размера и гармонично дополняют интерьер любой комнаты.",
// "[Название товара] — стильные и практичные кассетные жалюзи, которые регулируют освещённость и создают комфортную атмосферу. Простая установка и долговечные материалы делают их универсальным решением для дома или офиса.",
// "Кассетные жалюзи обеспечивают мягкое рассеянное освещение, защищают от солнца и посторонних взглядов. Лёгкий монтаж и разнообразие цветов и тканей позволяют подобрать вариант под любой интерьер.",
// "Эти кассетные жалюзи помогают создать гармоничный и уютный интерьер, обеспечивая комфорт и функциональность. Они легко устанавливаются и подходят для окон любого размера, создавая завершённый вид помещения.",
// "[Название товара] — универсальные кассетные жалюзи, которые защищают от солнца, регулируют свет и создают комфорт в комнате. Простая установка и качественные материалы обеспечивают долговечность и стильный вид.",
// "Кассетные жалюзи идеально подходят для любого интерьера, создавая уют и защищая от яркого солнца. Лёгкий монтаж и широкий выбор тканей позволяют подобрать вариант под стиль вашей комнаты.",
// "Эти жалюзи обеспечивают комфорт, функциональность и стиль. Они легко устанавливаются, регулируют освещённость и защищают от посторонних взглядов, создавая уютную и завершённую атмосферу в помещении.",
// "[Название товара] — практичные и надёжные кассетные жалюзи с современным дизайном. Они регулируют свет, защищают от яркого солнца и посторонних взглядов, легко монтируются и подходят для любых окон.",
// "Кассетные жалюзи создают гармонию интерьера, обеспечивая комфортное освещение и уют. Простая установка и разнообразие цветов и тканей делают их идеальным решением для дома, офиса или квартиры.",
// "Эти кассетные жалюзи помогают регулировать свет, создают уютную атмосферу и защищают от яркого солнца. Они подходят для окон любого размера и гармонично дополняют интерьер любой комнаты.",
// "[Название товара] — стильные кассетные жалюзи для дома и офиса. Они легко устанавливаются, обеспечивают защиту от солнца и посторонних взглядов, создавая уют и комфорт в любой комнате.",
// "Кассетные жалюзи создают функциональное и эстетичное оформление окна, обеспечивая мягкое освещение и комфорт. Они легко монтируются, подходят для окон разных размеров и гармонично сочетаются с интерьером.",
// "Эти жалюзи обеспечивают уют, комфорт и функциональность в помещении. Они защищают от солнца и посторонних взглядов, легко устанавливаются и подходят для окон любого размера, создавая завершённый интерьер.",
// "[Название товара] — универсальные кассетные жалюзи с современным дизайном. Они помогают регулировать освещённость, защищают от солнца и создают уют, подходя для любых окон и стилей интерьера.",
// "Кассетные жалюзи создают уют и комфорт в любой комнате, регулируя свет и защищая от яркого солнца. Лёгкий монтаж и долговечные материалы делают их практичным и стильным решением для дома и офиса.",
// "Эти кассетные жалюзи обеспечивают функциональность и эстетику. Они легко устанавливаются, защищают от солнца и посторонних взглядов, создают мягкое освещение и гармоничное оформление любого интерьера.",
// "[Название товара] — надёжные и практичные кассетные жалюзи, которые обеспечивают комфорт, регулируют освещённость и защищают от яркого солнца. Простая установка и разнообразие тканей делают их универсальным решением.",
// "Кассетные жалюзи помогают создать уют и стиль в помещении. Они легко монтируются, подходят для окон любого размера, защищают от солнца и посторонних взглядов, обеспечивая функциональность и комфорт.",
// "Эти жалюзи создают гармоничное оформление окна, регулируют свет и защищают от яркого солнца. Они подходят для любых помещений, легко устанавливаются и делают интерьер уютным и завершённым.",
// "[Название товара] — стильные и удобные кассетные жалюзи для дома и офиса. Они обеспечивают комфортное освещение, защищают от солнца и посторонних взглядов и легко монтируются на любые окна.",
// "Кассетные жалюзи создают уют и гармонию в интерьере, регулируя свет и защищая от посторонних взглядов. Лёгкий монтаж и разнообразие тканей и цветов делают их практичным и эстетичным решением для любого помещения.",
// "Эти жалюзи обеспечивают мягкое освещение, комфорт и функциональность. Они легко монтируются, защищают от яркого солнца и посторонних взглядов, создавая уютную атмосферу в спальне, гостиной или офисе.",
// "[Название товара] — универсальные кассетные жалюзи, которые помогают создать стильный и комфортный интерьер. Они регулируют освещённость, защищают от солнца и посторонних взглядов, легко монтируются и подходят для любых окон.",
// "Кассетные жалюзи создают уют и комфорт в помещении, обеспечивая мягкое рассеянное освещение. Простая установка и долговечные материалы делают их практичным и надёжным решением для дома, офиса или квартиры.",
// "Эти жалюзи помогают гармонично оформить интерьер, защищая от солнца и посторонних взглядов. Они легко устанавливаются, подходят для окон любого размера и создают уютную, комфортную атмосферу.",
// "[Название товара] — стильные кассетные жалюзи, которые обеспечивают функциональность и комфорт. Они регулируют свет, защищают от яркого солнца и посторонних взглядов, подходят для любых окон и стилей интерьера.",
// "Кассетные жалюзи помогают создать уют и гармонию в комнате. Они обеспечивают мягкое освещение, защищают от солнца и посторонних взглядов, легко монтируются и подходят для окон любого размера.",
// "Эти жалюзи создают функциональное оформление окна, обеспечивают комфорт и стиль. Они легко монтируются, регулируют освещённость, защищают от солнца и создают завершённый интерьер в любой комнате.",
// "[Название товара] — практичные и эстетичные кассетные жалюзи, которые защищают от солнца, регулируют свет и создают уют в помещении. Они легко устанавливаются и подходят для окон любого размера и стиля.",
// "Кассетные жалюзи создают уют, комфорт и функциональность в интерьере. Они регулируют освещённость, защищают от посторонних взглядов и яркого солнца, легко монтируются и гармонично дополняют любой стиль помещения",
//         ];

//         $firstTextArr =[
// "Кассетные жалюзи создают комфорт и уют в помещении, регулируя освещённость и защищая от яркого солнца и посторонних взглядов. Они легко монтируются, подходят для окон любого размера и гармонично вписываются в интерьер спальни, гостиной или офиса, обеспечивая стиль и функциональность.",
// "Практичные кассетные жалюзи обеспечивают мягкое рассеянное освещение, защищают от солнца и посторонних взглядов, создавая уютную атмосферу в комнате. Простая установка и долговечные материалы делают их удобным и надёжным решением для любого интерьера.",
// "Эти жалюзи помогут создать гармоничный интерьер, обеспечивая комфорт и защиту от яркого солнца. Они подходят для окон любого размера, легко монтируются и идеально сочетаются с мебелью и декором, превращая помещение в уютное и функциональное пространство для отдыха или работы.",
// "Кассетные жалюзи создают стильный и практичный интерьер, регулируя освещённость и сохраняя приватность. Они легко монтируются на любые окна, долговечны и просты в уходе, позволяя наслаждаться комфортом и эстетикой без лишних усилий, делая комнату уютной и завершённой.",
// "[Название товара] — это кассетные жалюзи, которые обеспечивают функциональность и комфорт. Они защищают от солнца и посторонних взглядов, создают мягкое освещение и гармонично вписываются в интерьер, делая пространство стильным, уютным и практичным для дома и офиса.",
// "Кассетные жалюзи позволяют создать уютную и комфортную атмосферу в помещении, защищая от яркого солнца и обеспечивая приватность. Простая установка, разнообразие тканей и цветов делают их универсальным решением для любого интерьера, сочетая стиль и функциональность.",
// "Эти кассетные жалюзи обеспечивают мягкое освещение и комфорт в помещении, защищая от посторонних взглядов и яркого солнца. Они легко монтируются, подходят для окон разных размеров и создают стильный и практичный интерьер для дома или офиса.",
// "Кассетные жалюзи помогают гармонично оформить интерьер, регулируя освещённость и защищая от солнца. Они подходят для любых помещений, легко устанавливаются и делают пространство уютным, комфортным и функциональным, обеспечивая завершённый и стильный вид комнаты.",
// "[Название товара] — практичные и надёжные кассетные жалюзи, которые создают уют и комфорт. Они регулируют свет, защищают от яркого солнца и посторонних взглядов, легко монтируются и подходят для окон любого размера, гармонично дополняя интерьер.",
// "Кассетные жалюзи создают функциональное и эстетичное оформление окна, обеспечивая мягкое освещение и уют. Они защищают от солнца и посторонних взглядов, легко устанавливаются, подходят для любых помещений и делают интерьер стильным и комфортным.",
// "Кассетные жалюзи создают уют и комфорт в комнате, защищая от яркого солнца и посторонних взглядов. Они легко монтируются, подходят для окон любого размера, долговечны и просты в уходе, обеспечивая функциональность и гармоничное оформление интерьера дома или офиса.",
// "Эти жалюзи обеспечивают мягкое рассеянное освещение, уют и практичность. Лёгкий монтаж и широкий выбор тканей и цветов позволяют подобрать идеальный вариант для спальни, гостиной или кабинета, создавая стильный и завершённый интерьер, гармонирующий с мебелью и декором.",
// "[Название товара] — кассетные жалюзи с современным механизмом и долговечными материалами. Они защищают от солнца, регулируют освещённость, создают уютную атмосферу и идеально подходят для любых окон, обеспечивая практичность и гармоничное оформление интерьера.",
// "Кассетные жалюзи помогают создать комфортный интерьер, обеспечивая мягкое освещение и защиту от посторонних взглядов. Простая установка и долговечные материалы делают их универсальным решением для дома или офиса, сочетая функциональность, стиль и уют в помещении.",
// "Эти жалюзи создают гармонию и уют в комнате, защищая от яркого солнца. Они легко монтируются, подходят для окон любого размера, обеспечивают мягкое освещение и приватность, превращая пространство в комфортное и стильное место для работы, отдыха или общения.",
// "Кассетные жалюзи обеспечивают функциональность, комфорт и эстетику интерьера. Они регулируют свет, защищают от солнца и посторонних взглядов, легко монтируются и подходят для любых окон, создавая уютную и завершённую атмосферу в доме или офисе.",
// "[Название товара] — практичные и стильные кассетные жалюзи, которые создают комфорт и уют в помещении. Они легко устанавливаются, защищают от солнца и посторонних взглядов, регулируют освещённость и гармонично дополняют интерьер любого стиля и назначения.",
// "Кассетные жалюзи создают уютную и функциональную атмосферу в помещении. Они защищают от яркого солнца, регулируют освещённость, легко монтируются на любые окна и подходят для разных интерьеров, обеспечивая практичность, комфорт и гармоничное оформление комнаты.",
// "Эти жалюзи создают мягкое освещение и комфорт в помещении, защищают от солнца и посторонних взглядов, легко монтируются и подходят для окон любого размера. Они помогают создать стильный и уютный интерьер для дома или офиса, сочетая практичность и эстетику.",
// "[Название товара] — надёжные кассетные жалюзи, которые создают комфорт и уют в комнате. Они регулируют свет, защищают от яркого солнца и посторонних взглядов, легко устанавливаются и подходят для любых окон, гармонично дополняя интерьер помещения.",
// "Кассетные жалюзи обеспечивают функциональность, уют и мягкое освещение в помещении. Лёгкий монтаж и разнообразие тканей позволяют подобрать идеальный вариант для спальни, гостиной или офиса, создавая гармоничный и стильный интерьер, защищающий от солнца и посторонних взглядов.",
// "Эти жалюзи помогают создать уютный и комфортный интерьер, регулируя освещённость и обеспечивая защиту от солнца. Они легко устанавливаются, подходят для окон любого размера и гармонично сочетаются с интерьером, мебелью и декоративными элементами.",
// "[Название товара] — практичные и стильные кассетные жалюзи, которые защищают от яркого солнца и посторонних взглядов. Они обеспечивают комфорт, регулируют свет, легко монтируются и создают уютную и завершённую атмосферу в помещении любого назначения.",
// "Кассетные жалюзи создают функциональное оформление окна, обеспечивая мягкое освещение, уют и комфорт. Они подходят для окон любого размера, легко устанавливаются и гармонично дополняют интерьер дома, офиса или квартиры, сочетая практичность и эстетику.",
// "Эти жалюзи обеспечивают комфорт, функциональность и стиль. Они легко монтируются, защищают от солнца и посторонних взглядов, создают уютную атмосферу и идеально подходят для любого помещения, превращая пространство в стильное и удобное.",
// "[Название товара] — надёжные кассетные жалюзи с долговечными материалами и современным механизмом. Они помогают регулировать освещённость, защищают от яркого солнца и посторонних взглядов, создавая уютный, стильный и функциональный интерьер.",
// "Кассетные жалюзи помогают создать гармоничный и уютный интерьер, обеспечивая комфорт и мягкое освещение. Лёгкий монтаж и разнообразие цветов позволяют подобрать оптимальный вариант для дома, офиса или квартиры, создавая стильное и практичное пространство.",
// "Эти жалюзи создают функциональный и эстетичный интерьер, регулируют освещённость и защищают от солнца. Они легко устанавливаются на окна любого размера, подходят для разных стилей и создают уютную атмосферу для работы, отдыха или общения.",
// "[Название товара] — практичные и универсальные кассетные жалюзи, которые обеспечивают комфорт, уют и мягкое освещение. Они защищают от яркого солнца и посторонних взглядов, легко монтируются и подходят для помещений любого назначения.",
// "Кассетные жалюзи создают гармоничное оформление окна, регулируют свет и защищают от солнца и посторонних взглядов. Простая установка и долговечные материалы делают их удобным и эстетичным решением для дома, офиса или квартиры.",
// "Эти жалюзи обеспечивают комфорт и уют в помещении, защищают от яркого солнца, легко монтируются и подходят для окон любого размера. Они помогают создать стильный и функциональный интерьер, который гармонично вписывается в любой дизайн.",
// "[Название товара] — стильные и практичные кассетные жалюзи, создающие мягкое освещение и уют. Они защищают от солнца и посторонних взглядов, легко устанавливаются, подходят для любых окон и гармонично дополняют интерьер любой комнаты.",
// "Кассетные жалюзи обеспечивают уют, комфорт и функциональность в комнате. Лёгкий монтаж и разнообразие тканей позволяют подобрать оптимальный вариант, защищающий от солнца и создающий гармоничный и стильный интерьер.",
// "Эти жалюзи помогают создать стильный и уютный интерьер, регулируя освещённость и защищая от яркого солнца. Они подходят для окон любого размера, легко монтируются и идеально сочетаются с мебелью, декором и общим стилем помещения.",
// "[Название товара] — универсальные кассетные жалюзи, которые создают комфорт и уют. Они защищают от солнца и посторонних взглядов, обеспечивают мягкое освещение и легко монтируются на окна любого размера, гармонично дополняя интерьер.",
// "Кассетные жалюзи создают уют и комфорт в помещении, защищая от солнца и посторонних взглядов. Они подходят для окон любого размера, легко монтируются и обеспечивают мягкое освещение, гармонично сочетаясь с интерьером и декоративными элементами.",
// "Эти жалюзи обеспечивают функциональность, комфорт и эстетику. Они легко устанавливаются, регулируют освещённость и защищают от солнца, создавая уютную и завершённую атмосферу в доме, офисе или квартире любого стиля.",
// "[Название товара] — надёжные и практичные кассетные жалюзи, которые помогают создать уют и комфорт в помещении. Они защищают от яркого солнца и посторонних взглядов, легко монтируются и подходят для окон любого размера, гармонично дополняя интерьер.",
// "Кассетные жалюзи помогают создать функциональное и эстетичное оформление окна, обеспечивая мягкое освещение и уют. Они легко монтируются, подходят для любых помещений и стилей, создавая комфортную и завершённую атмосферу в доме или офисе.",
// "Эти жалюзи создают гармоничный интерьер, защищая от солнца и посторонних взглядов. Они легко устанавливаются, подходят для окон любого размера, обеспечивают мягкое освещение и уют, создавая стильное и практичное пространство для дома или офиса.",
// "[Название товара] — практичные и стильные кассетные жалюзи, обеспечивающие комфорт и функциональность. Они защищают от солнца, регулируют свет и легко монтируются, создавая уютную атмосферу и гармоничное оформление интерьера любого помещения.",
// "Кассетные жалюзи создают уют, мягкое освещение и комфорт. Они легко устанавливаются, подходят для окон любого размера, защищают от яркого солнца и посторонних взглядов, гармонично дополняя интерьер дома, офиса или квартиры.",
// "Эти жалюзи помогают создать функциональный и стильный интерьер, обеспечивая мягкое освещение, уют и комфорт. Они легко монтируются, подходят для окон любого размера и защищают от солнца и посторонних взглядов.",
// "[Название товара] — надёжные кассетные жалюзи, создающие комфорт и уют. Они регулируют свет, защищают от яркого солнца, легко монтируются и подходят для любых окон, гармонично сочетаясь с интерьером любого помещения.",
// "Кассетные жалюзи создают гармонию и уют в комнате, защищая от солнца и посторонних взглядов. Они подходят для окон любого размера, легко устанавливаются и обеспечивают мягкое освещение, функциональность и стильное оформление интерьера.",
// "Эти жалюзи обеспечивают комфорт, уют и функциональность в помещении. Лёгкий монтаж и разнообразие тканей позволяют подобрать оптимальный вариант, создавая мягкое освещение и защищая от яркого солнца и посторонних взглядов.",
// "[Название товара] — универсальные и стильные кассетные жалюзи, которые помогают создать уют и комфорт. Они защищают от солнца и посторонних взглядов, регулируют свет и легко монтируются, гармонично вписываясь в интерьер.",
// "Кассетные жалюзи создают функциональное оформление окна, обеспечивая мягкое освещение, уют и комфорт. Они легко устанавливаются на любые окна, подходят для разных стилей интерьера и защищают от солнца и посторонних взглядов.",
// "Эти жалюзи помогают создать уютный и комфортный интерьер, регулируя свет и защищая от яркого солнца. Они подходят для окон любого размера, легко монтируются и гармонично дополняют стиль комнаты, создавая функциональное и эстетичное пространство",
//         ];

//         $charArr =[
// "Перед вами подробные технические характеристики кассетных жалюзи, включающие размеры, материалы и особенности крепления. Эти данные помогут выбрать оптимальный вариант для вашего интерьера и гарантируют функциональность, надёжность и долговечность изделия.",
// "Ниже представлены основные параметры кассетных жалюзи, включая материал карниза, способ управления и максимальные габариты. Тщательно изучив эти характеристики, вы сможете подобрать модель, идеально подходящую для вашей комнаты, дома или офиса.",
// "В этом разделе собраны все технические данные кассетных жалюзи: от размеров карниза до способов управления и типовых ограничений. Эти сведения помогут оценить функциональность изделия и сделать правильный выбор для комфортного интерьера.",
// "Ознакомьтесь с полным перечнем технических характеристик кассетных жалюзи. Здесь указаны материалы, цветовые решения, способы крепления и управления, а также максимальные размеры изделия — всё, что важно для точного подбора модели.",
// "Представляем вам детальные технические параметры кассетных жалюзи. Они включают информацию о материале карниза, способе управления, монтаже и ограничениях по габаритам, что позволит грамотно оценить практичность и удобство использования изделия.",
// "Ниже приведён подробный список технических характеристик кассетных жалюзи. Эти данные помогут определить совместимость с интерьером, подобрать удобный способ управления и рассчитать необходимые размеры для окна.",
// "В этом блоке собраны все ключевые параметры кассетных жалюзи: материал, размеры, управление и монтаж. Знание этих характеристик обеспечит правильный выбор модели, подходящей по функциональности и внешнему виду для вашего помещения.",
// "Перед вами технические характеристики кассетных жалюзи, включающие информацию о карнизе, креплении, управлении и максимальных размерах. Эти данные помогут подобрать изделие, которое сочетает удобство, стиль и долговечность.",
// "Ознакомьтесь с основными техническими данными кассетных жалюзи: материал карниза, способ крепления, управление и максимальные габариты. Они позволят оценить практичность изделия и выбрать подходящую модель для вашего окна.",
// "Ниже представлены ключевые технические параметры кассетных жалюзи. Они помогут понять конструктивные особенности изделия, оценить его функциональность и подобрать оптимальный вариант для любых окон и интерьеров.",
// "Этот блок содержит все важные характеристики кассетных жалюзи: размеры, материалы, монтаж и управление. Изучив их, вы сможете выбрать изделие, которое будет удобно использовать и идеально впишется в интерьер вашей комнаты.",
// "Представляем полные технические данные кассетных жалюзи, включая материал, цвет, способ управления, крепление и ограничения по размерам. Эта информация поможет подобрать изделие, соответствующее вашим требованиям по функциональности и стилю.",
// "Ознакомьтесь с детальной спецификацией кассетных жалюзи. Здесь указаны материал карниза, способы управления и крепления, максимальные размеры и другие параметры, которые помогут оценить удобство использования и долговечность изделия.",
// "Перед вами блок с техническими характеристиками кассетных жалюзи. Все данные — от материала и цвета до способов управления и монтажных особенностей — помогут выбрать оптимальную модель для вашего интерьера.",
// "Ниже указаны ключевые параметры кассетных жалюзи: материал карниза, крепление, управление и максимальные размеры изделия. Эта информация позволит подобрать удобный и функциональный вариант для дома или офиса.",
// "В этом разделе собраны полные технические характеристики кассетных жалюзи. Они включают материал, размеры, способ управления, тип крепления и максимальные габариты, что поможет сделать точный выбор изделия для любого помещения.",
// "Перед вами техническая информация о кассетных жалюзи: материал, цвет, способ управления и габариты. Эти данные помогут подобрать модель, которая будет сочетать комфорт, практичность и стильный внешний вид.",
// "Ознакомьтесь с подробными характеристиками кассетных жалюзи: материал, управление, монтаж и размеры. Знание этих параметров поможет сделать правильный выбор, обеспечивающий функциональность и эстетику изделия.",
// "Ниже представлены технические параметры кассетных жалюзи. Изучение этих характеристик — материал карниза, способ управления, крепление и максимальные размеры — позволит выбрать модель, идеально подходящую для вашего интерьера.",
// "В этом блоке собраны все основные технические данные кассетных жалюзи, включая материал, способ монтажа, управление и максимальные размеры. Эти сведения помогут подобрать практичное и стильное изделие для любого окна",
// "Этот блок содержит полные технические данные кассетных жалюзи. Здесь указаны материал карниза, способ управления, крепление и размеры. Изучив эти параметры, вы сможете выбрать изделие, которое идеально впишется в интерьер и обеспечит максимальный комфорт при эксплуатации.",
// "Ознакомьтесь с основными характеристиками кассетных жалюзи. Включены материалы, способы управления, крепление и габариты изделия. Эти сведения помогут подобрать оптимальную модель, которая будет удобной, функциональной и долговечной для любого помещения.",
// "Ниже представлены ключевые параметры кассетных жалюзи. Информация о материале, цвете, монтаже и способах управления позволит сделать правильный выбор изделия, обеспечивающего комфорт, эстетику и долговечность при ежедневном использовании.",
// "В этом разделе вы найдёте технические характеристики кассетных жалюзи, включая материал карниза, размеры, способ крепления и управления. Эти данные помогут оценить практичность изделия и подобрать модель, идеально подходящую для вашего интерьера.",
// "Перед вами детальный перечень технических характеристик кассетных жалюзи. Здесь указаны материал, цвет, способ управления, крепление и максимальные размеры, что позволит выбрать практичное, удобное и стильное решение для любого помещения.",
// "Ознакомьтесь с полным набором технических данных кассетных жалюзи. Информация о материале, способе монтажа, управлении и габаритах изделия поможет подобрать оптимальный вариант для дома, офиса или квартиры с любым стилем интерьера.",
// "В этом блоке представлены технические параметры кассетных жалюзи, включая материал карниза, способ управления, крепление и размеры. Изучив их, вы сможете выбрать изделие, которое будет удобным в эксплуатации и гармонично впишется в интерьер комнаты.",
// "Ниже указаны все важные характеристики кассетных жалюзи: материал, цвет, монтаж, способ управления и габариты изделия. Эти сведения помогут оценить удобство и практичность использования, а также подобрать изделие, соответствующее вашему интерьеру.",
// "Этот блок содержит подробные технические данные кассетных жалюзи. Здесь указаны материал карниза, крепление, способы управления и размеры изделия, что позволит выбрать модель, которая будет максимально функциональной, удобной и эстетичной.",
// "Ознакомьтесь с ключевыми характеристиками кассетных жалюзи, включая материал, управление, крепление и размеры. Эти данные помогут выбрать изделие, которое сочетает стиль, практичность и долговечность, обеспечивая комфорт в помещении любого назначения.",
// "Перед вами полный перечень технических параметров кассетных жалюзи. Изучив материалы, способ монтажа, управление и габариты, вы сможете подобрать удобное, практичное и долговечное изделие, которое гармонично дополнит интерьер комнаты.",
// "Ниже приведены основные характеристики кассетных жалюзи, включая материал карниза, способ управления, монтаж и максимальные размеры. Эти сведения помогут сделать правильный выбор, обеспечив удобство, функциональность и стиль в интерьере.",
// "В этом блоке представлены технические данные кассетных жалюзи: материал, цвет, способ управления, монтаж и габариты. Эти параметры помогут подобрать изделие, которое будет удобным, долговечным и гармонично впишется в интерьер вашего помещения.",
// "Ознакомьтесь с детальными характеристиками кассетных жалюзи. Здесь указаны материал, способ крепления, управление и размеры изделия, что поможет выбрать оптимальный вариант для создания комфортного и функционального интерьера.",
// "Этот блок содержит полные технические характеристики кассетных жалюзи. Информация о материале, способе управления, креплении и размерах поможет подобрать изделие, которое будет удобным, практичным и гармонично дополнять интерьер.",
// "Ниже представлены важные параметры кассетных жалюзи: материал карниза, монтаж, способ управления и габариты. Изучив эти данные, вы сможете выбрать изделие, которое сочетает функциональность, долговечность и стильное оформление интерьера.",
// "В этом разделе собраны технические характеристики кассетных жалюзи, включая материал, способ управления, крепление и размеры. Эти сведения помогут сделать правильный выбор изделия, обеспечивающего комфорт, удобство и эстетику в комнате.",
// "Ознакомьтесь с полным перечнем технических данных кассетных жалюзи. Здесь указаны материал, способ монтажа, управление и габариты, что позволит подобрать практичное, надёжное и стильное изделие для дома, офиса или квартиры.",
// "Этот блок содержит все ключевые параметры кассетных жалюзи: материал карниза, способ управления, крепление и максимальные размеры. Эти сведения помогут оценить функциональность изделия и выбрать подходящую модель для вашего интерьера.",
// "Ниже представлены подробные технические характеристики кассетных жалюзи. Изучив материал, монтаж, способ управления и размеры, вы сможете подобрать изделие, которое обеспечит комфорт, удобство и гармоничное оформление любой комнаты",
//         ];


//         $modelIds = [77]; 

//         $products = Product::whereIn('model_id', $modelIds)->get();
 
//         shuffle($titlesArr);


//         $tableHtml = <<<HTML
//             <div class="charTableWrap">
//                 <table border="" cellpadding="5" cellspacing="0">
//                     <thead>
//                         <tr>
//                             <th colspan="2">Технические характеристики</th>
//                         </tr>
//                         <tr>
//                             <th>Параметр</th>
//                             <th>Значение</th>
//                         </tr>
//                     </thead>
//                     <tbody>
//                         <tr><td>Система</td><td>Isotra Hit 2 (Чехия)</td></tr>
//                         <tr><td>Материал карниза</td><td>Сталь</td></tr>
//                         <tr><td>Крепление карниза</td><td>Саморезы</td></tr>
//                         <tr><td>Ширина ламелей</td><td>25 мм.</td></tr>
//                         <tr><td>Толщина ламелей</td><td>18 микрон</td></tr>
//                         <tr><td>Пробивка ламелей</td><td>Обычная</td></tr>
//                         <tr><td>Материал нижней планки</td><td>Сталь</td></tr>
//                         <tr><td>Управление</td><td>Монокомандное (цепочка)</td></tr>
//                         <tr><td>Доп. опция</td><td>Усиленный механизм</td></tr>
//                         <tr><td>Автоматизация</td><td>Нет</td></tr>
//                     </tbody>
//                 </table>
//             </div>
//             HTML;

//         $i = 0;
//         foreach ($products as $product) {
//             $templateTitle = $titlesArr[$i % count($titlesArr)];
//             $templateDescr = $descriptionArr[$i % count($descriptionArr)];
//             $templateFirstText = $firstTextArr[$i % count($firstTextArr)];
//             $templateCars = $charArr[$i % count($charArr)];

//             $textTitle = str_replace('[Название товара]', $product->h1, $templateTitle);
//             $textDescr = str_replace('[Название товара]', $product->h1, $templateDescr);
//             $textFirst = str_replace('[Название товара]', $product->h1, $templateTitle);
//             $textChar = str_replace('[Название товара]', $product->h1, $templateCars);

//             $product->title = $textTitle;
//             $product->description = $textDescr;
//             $product->first_screenn_description = $textFirst;
//             $product->characteristic = "<p>{$textChar}</p>" . $tableHtml;

//             $product->save();

//             $i++;
//         }



        $allIds = range(7,80);
        $excludedIds = [14];
        $filteredIds = array_diff($allIds, $excludedIds);

        $products = Product::where('category_id', 1)
        ->whereIntegerInRaw('subcategory_id', $filteredIds)
        ->get();

        foreach($products as $product){
            $product->seo=null;
            $product->save();
            // $product->delete();
        }

    }
}
