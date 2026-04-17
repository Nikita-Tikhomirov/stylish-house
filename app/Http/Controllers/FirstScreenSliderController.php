<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FirstScreenSlider;
use App\Models\Product;


class FirstScreenSliderController extends Controller
{



    // Метод для сохранения нового слайда
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'required|string|max:255',
            'description_start' => 'required|string|max:255',
            'description_colored' => 'required|string|max:255',
            'description_end' => 'required|string|max:255',
            'model_id' => 'required|string|max:255', // 'product_id' больше не нужен
        ]);

        // Получаем первый продукт, соответствующий переданному model_id
        $product = Product::where('model_id', $validatedData['model_id'])->first();

        // Если продукт найден, используем его id, иначе можно обработать ошибку
        if ($product) {
            FirstScreenSlider::create([
                'title' => $validatedData['title'],
                'subtitle' => $validatedData['subtitle'],
                'description_start' => $validatedData['description_start'],
                'description_colored' => $validatedData['description_colored'],
                'description_end' => $validatedData['description_end'],
                'product_id' => $product->id, // Используем id найденного продукта
                'model_id' => $validatedData['model_id'],
            ]);

            return response()->json(['success' => true]);
        } else {
            return response()->json(['error' => 'Product not found for the given model_id'], 404);
        }
    }


    // Метод для отображения формы редактирования

    // Метод для обновления слайда
    public function update(Request $request, $id)
    {
        try {
            $data = $request->validate([
                'title' => 'required|string|max:255',
                'subtitle' => 'required|string|max:255',
                'description_start' => 'required|string|max:255',
                'description_colored' => 'required|string|max:255',
                'description_end' => 'required|string|max:255',
                // 'product_id' => 'required|string|max:255',
                'model_id' => 'required|string|max:255',
            ]);

            // Обновление слайда
            $slider = FirstScreenSlider::findOrFail($id);
            $slider->update($data);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        // Удаление слайда
        FirstScreenSlider::destroy($id);

        return response()->json(['success' => true]);
    }
}
