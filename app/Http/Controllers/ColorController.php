<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use ColorThief\ColorThief;
use App\Models\Product;
use App\Models\Tab;

class ColorController extends Controller
{
    public function index()
    {
        $directory = storage_path('app/colors'); // Путь до папки colors
        $files = glob("$directory/*.{jpg,jpeg,png}", GLOB_BRACE);
        $colors = [];
        set_time_limit(120);

        foreach ($files as $file) {
            $filename = pathinfo($file, PATHINFO_FILENAME);
            try {
                $rgb = ColorThief::getColor($file);
                $hexColor = sprintf("#%02X%02X%02X", $rgb[0], $rgb[1], $rgb[2]);
                $colors[$hexColor] = $filename;
            } catch (\Exception $e) {
                continue;
            }
        }

        // Возвращаем массив цветов и названий файлов
        return response('<pre>' . htmlspecialchars(print_r($colors, true)) . '</pre>')
        ->header('Content-Type', 'text/html; charset=UTF-8');

        // $modelId = 78; // замени на нужный ID

        // $products = Product::where('model_id', $modelId)->get();

        // foreach ($products as $product) {
        //     // Удалим связанные табы
        //     Tab::where('product_id', $product->id)->delete();

        //     // Удалим сам товар
        //     $product->delete();
        // }
    }
}
