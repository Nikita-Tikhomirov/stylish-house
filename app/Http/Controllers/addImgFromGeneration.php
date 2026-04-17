<?php

namespace App\Http\Controllers;
use App\Models\Product;
use App\Services\ProductImageThumbnailService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class addImgFromGeneration extends Controller
{
// public function addImgFromGeneration(Request $request)
// {
//     $products = Product::where('model_id', 71)->get();

//     $files = collect(Storage::disk('public')->files('products/bambuk-50'))
//         ->filter(fn($file) => in_array(Str::lower(pathinfo($file, PATHINFO_EXTENSION)), ['png','jpg','jpeg']));

//     Log::info('Найдено файлов: ' . $files->count());

//     // Собираем карта: файл → числовой код (после 25-)
//     $fileCodes = [];
//     foreach ($files as $file) {
//         $filename = pathinfo($file, PATHINFO_FILENAME);

//         if (preg_match('/50KT-(\d+)/', $filename, $m)) {
//             $fileCodes[$file] = $m[1]; // только цифры кода
//         } else {
//             Log::warning("Файл {$file} не содержит код вида 50-XXXX");
//         }
//     }

//     foreach ($products as $product) {
//         $h1 = $product->h1;

//         Log::info("Обрабатывается товар {$product->id}: {$h1}");

//         $matchedFile = null;

//         foreach ($fileCodes as $file => $code) {

//             // Строгое совпадение:
//             // 1) 25-109 (полный вид)
//             // 2) код как отдельное число (например "109")
//             $patternFull = '/50KT-' . preg_quote($code, '/') . '\b/u';
//             $patternNumberOnly = '/\b' . preg_quote($code, '/') . '\b/u';

//             if (preg_match($patternFull, $h1) || preg_match($patternNumberOnly, $h1)) {
//                 $matchedFile = $file;
//                 Log::info("Совпадение найдено: файл {$file} (код {$code}) => товар {$product->id}");
//                 break;
//             }
//         }

//         if ($matchedFile) {
//             $product->image_path = 'storage/' . $matchedFile;
//             $product->save();
//             Log::info("Сохранено изображение для товара {$product->id}: {$matchedFile}");
//         } else {
//             Log::warning("Нет совпадения для товара {$product->id}: {$h1}");
//         }
//     }

//     return 'Готово';
// }

public function addImgFromGeneration(Request $request)
{
    $thumbnailService = app(ProductImageThumbnailService::class);

    $products = Product::where('model_id', 75)->get();

    $files = collect(Storage::disk('public')->files('products/isolite-25'))
        ->filter(fn($file) => in_array(Str::lower(pathinfo($file, PATHINFO_EXTENSION)), ['png','jpg','jpeg']));

    Log::info('Найдено файлов: ' . $files->count());

    // Преобразуем список файлов в удобный формат (нормализованное имя + токены)
    $fileMap = $files->mapWithKeys(function ($file) {
        $filename = pathinfo($file, PATHINFO_FILENAME);
        $norm = $this->normalizeSimple($filename);
        return [$file => [
            'norm' => $norm,
            'tokens' => $this->tokenize($norm),
        ]];
    });

    // 🔥 ВЫТАСКИВАЕМ АРТИКУЛЫ ИЗ ФАЙЛОВ (25-104 → 104)
    $fileCodes = [];
    foreach ($fileMap as $file => $data) {
        if (preg_match('/25[^\d]?(\d+)/', $data['norm'], $m)) {
            $fileCodes[$file] = $m[1];
        }
    }

    foreach ($products as $product) {
        $normalizedH1 = $this->normalizeSimple($product->h1);
        $productTokens = $this->tokenize($normalizedH1);

        Log::info("Обрабатывается товар: {$product->id} | {$product->h1} | нормализовано: {$normalizedH1}");

        $matchedFile = null;

        // ==========================================================
        // 1) ПРИОРИТЕТ: СТРОГО ПО АРТИКУЛУ 25-XXX
        // ==========================================================
        preg_match_all('/25[^\d]?(\d+)/', $normalizedH1, $m);
        $productCodes = $m[1] ?? [];

        if (!empty($productCodes)) {
            // берём самый длинный код (104 вместо 1)
            usort($productCodes, fn($a,$b) => strlen($b) <=> strlen($a));

            foreach ($productCodes as $code) {
                foreach ($fileCodes as $file => $fileCode) {
                    if ($fileCode === $code) {
                        $matchedFile = $file;
                        Log::info("НАЙДЕН ФАЙЛ (по коду 25-{$code}): {$file} для товара {$product->id}");
                        break 2;
                    }
                }
            }
        }

        // ==========================================================
        // 2) СТАРЫЙ МЕХАНИЗМ (как для 16мм) — если не нашли по коду
        // ==========================================================
        if (!$matchedFile) {
            foreach ($fileMap as $file => $data) {
                if (
                    $normalizedH1 === $data['norm'] ||
                    str_contains($normalizedH1, $data['norm']) ||
                    str_contains($data['norm'], $normalizedH1)
                ) {
                    $matchedFile = $file;
                    Log::info("НАЙДЕН ФАЙЛ (старый алгоритм): {$file} для товара {$product->id}");
                    break;
                }
            }
        }

        // ==========================================================
        // 3) ФОЛБЕК ПО ТОКЕНАМ (как у тебя было)
        // ==========================================================
        if (!$matchedFile) {
            $best = null;
            $bestScore = 0;
            foreach ($fileMap as $file => $data) {
                $intersection = array_intersect($productTokens, $data['tokens']);
                $score = count($intersection) / max(1, count($data['tokens']));
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $best = $file;
                }
            }

            if ($best && $bestScore >= 0.6) {
                $matchedFile = $best;
                Log::info("НАЙДЕН ФАЙЛ (по токенам): {$best} score={$bestScore} для товара {$product->id}");
            }
        }

        // ==========================================================
        // СОХРАНЕНИЕ
        // ==========================================================
        if ($matchedFile) {
            $product->image_path = 'storage/' . $matchedFile;
            $product->save();
            $thumbnailResult = $thumbnailService->generateForProduct($product, ['force' => false]);
            if (Schema::hasColumn('products', 'image_thumb_path') && !empty($thumbnailResult['thumbnail_public_path'])) {
                $product->image_thumb_path = $thumbnailResult['thumbnail_public_path'];
                $product->save();
            }
            if (($thumbnailResult['status'] ?? null) === 'error') {
                Log::warning("Thumbnail generation failed for product {$product->id}: " . ($thumbnailResult['reason'] ?? 'unknown'));
            }
            Log::info("Сохранено: product {$product->id} image_path={$product->image_path}");
        } else {
            Log::warning("Не найден файл для товара: {$product->id} | {$product->h1} | нормализовано: {$normalizedH1}");
        }
    }

    return 'Готово';
}



// public function addImgFromGeneration(Request $request)
// {
//     $modelIds = [77];
//     $products = Product::whereIn('model_id', $modelIds)->get();

//     // Папка с фото
//     $path = 'materials/jaluzi';

//     // Берём только изображения, начинающиеся на 25-
//     $files = collect(Storage::disk('public')->files($path))
//         ->filter(function($file){
//             $ext = Str::lower(pathinfo($file, PATHINFO_EXTENSION));
//             $name = pathinfo($file, PATHINFO_FILENAME);
//             return in_array($ext, ['png','jpg','jpeg'])
//                 && preg_match('/^25-(\d+)/', $name);
//         });

//     Log::info('Найдено файлов: ' . $files->count());

//     // Собираем карту: файл → код (цифры после 25-)
//     $fileCodes = [];
//     foreach ($files as $file) {
//         $filename = pathinfo($file, PATHINFO_FILENAME);

//         if (preg_match('/^25-(\d+)/', $filename, $m)) {
//             $fileCodes[$file] = $m[1]; // только цифры (например 10)
//         }
//     }
//     foreach ($products as $product) {
//         $h1 = $product->h1;
//         Log::info("Товар {$product->id}: {$h1}");

//         // Ищем в названии ВСЕ вхождения вида 25-числа
//         preg_match_all('/25-(\d+)/u', $h1, $matches);

//         if (empty($matches[1])) {
//             Log::warning("В товаре нет кода 25-XXX: {$h1}");
//             continue;
//         }

//         // Берём самый длинный код (104 вместо 1)
//         $productCode = collect($matches[1])
//             ->sortByDesc(fn($v) => strlen($v))
//             ->first();

//         Log::info("Найден код товара: 25-{$productCode}");

//         $matchedFile = null;

//         foreach ($fileCodes as $file => $code) {
//             if ($code === $productCode) {
//                 $matchedFile = $file;
//                 break;
//             }
//         }

//         if ($matchedFile) {
//             $product->fabric_photo = 'storage/' . $matchedFile;
//             $product->save();

//             Log::info("Фото привязано → {$matchedFile} для товара {$product->id}");
//         } else {
//             Log::warning("Файл 25-{$productCode}.* не найден для товара {$product->id}");
//         }
//     }



//     return 'Готово';
// }


private function normalizeSimple(string $string): string
{
    $string = mb_strtolower($string);
    // Заменяем все не-буквы/не-цифры на пробел (сохраняем разделители как пробелы)
    $string = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $string);
    // Сжимаем множественные пробелы и обрезаем
    $string = trim(preg_replace('/\s+/u', ' ', $string));
    return $string;
}

private function tokenize(string $string): array
{
    $words = preg_split('/\s+/u', $string, -1, PREG_SPLIT_NO_EMPTY);
    // убираем короткие "шумные" слова длиной 1 символ (опционально)
    $words = array_values(array_filter($words, fn($w) => mb_strlen($w) > 1));
    return $words;
}


}





// class addImgFromGeneration extends Controller
// {
//     public function addImgFromGeneration(Request $request)
//     {
//         $products = Product::where('model_id', 37)->get();
//         $h1s = $products->pluck('h1'); // если поле называется h1
//         dd($h1s);


//     }


// }


// Удаление пробелов

// public function addImgFromGeneration(Request $request)
// {
//     $products = Product::where('model_id', 37)->get();

//     foreach ($products as $product) {
//         $cleanH1 = Str::squish($product->h1); // убираем лишние пробелы
//         if ($cleanH1 !== $product->h1) {
//             $product->h1 = $cleanH1;
//             $product->save();
//         }
//     }

//     return 'Готово: лишние пробелы удалены';
// }
