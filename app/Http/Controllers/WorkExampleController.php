<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WorkExample;
use Illuminate\Support\Facades\Storage;

class WorkExampleController extends Controller
{
    // Метод для загрузки нескольких изображений
    public function store(Request $request)
    {
        $request->validate([
            'images.*' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'category_id' => 'nullable|string',
            'subcategory_id' => 'nullable|string',

        ]);

        $images = $request->file('images');
        $savedImages = [];

        if ($images) {
            foreach ($images as $image) {
                // Сохраняем изображение в директорию 'public/storage/work_examples'
                $path = $image->store('work_examples', 'public');
                $savedImages[] = WorkExample::create([
                    'image' => $path,
                    'title' => '', // Оставляем пустыми до редактирования
                    'description' => '',
                    'category_id' => $request->category_id ,// Привязываем к категории
                    'subcategory_id' => $request->subcategory_id // Привязываем к категории

                ]);
            }
        }

        return response()->json($savedImages); // Возвращаем JSON с данными изображений
    }

    // Метод для обновления отдельного изображения
    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'string|nullable',
            'description' => 'string|nullable'
        ]);

        $workExample = WorkExample::findOrFail($id);
        $workExample->update($request->only('title', 'description'));

        return response()->json(['success' => true, 'workExample' => $workExample]);
    }

    // Метод для удаления изображения
    public function destroy($id)
    {
        $workExample = WorkExample::findOrFail($id);

        // Удаление файла изображения
        Storage::delete('public/' . $workExample->image);

        // Удаление записи из базы данных
        $workExample->delete();

        return response()->json(['success' => true]);
    }
}
