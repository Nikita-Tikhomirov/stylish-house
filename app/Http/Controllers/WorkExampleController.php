<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WorkExample;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic as Image;

class WorkExampleController extends Controller
{
    // Метод для загрузки нескольких изображений
    public function store(Request $request)
    {
        $request->validate([
            'images.*' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:20480',
            'category_id' => 'nullable|string',
            'subcategory_id' => 'nullable|string',
        ]);

        $images = $request->file('images');
        $savedImages = [];

        if ($images) {
            foreach ($images as $image) {
                // Генерируем имя файла
                $filename = time() . '_' . uniqid() . '.webp';

                // Обрабатываем через Intervention
                $img = Image::make($image->getRealPath())->orientate();

                // 1. Большое фото: ресайз до максимум 1200px по большей стороне
                $img->resize(1200, 1200, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });

                $mainPath = 'work_examples/' . $filename;
                Storage::disk('public')->put($mainPath, (string) $img->encode('webp', 82));

                // 2. Миниатюра: 400x300, обрезка по центру
                $thumb = Image::make($image->getRealPath())->orientate();
                $thumb->fit(400, 300, function ($constraint) {
                    $constraint->upsize();
                });

                $thumbPath = 'work_examples/thumbs/' . $filename;
                Storage::disk('public')->put($thumbPath, (string) $thumb->encode('webp', 75));

                $savedImages[] = WorkExample::create([
                    'image' => $mainPath,
                    'thumb' => $thumbPath,
                    'title' => '',
                    'description' => '',
                    'category_id' => $request->category_id,
                    'subcategory_id' => $request->subcategory_id,
                ]);
            }
        }

        return response()->json($savedImages);
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

    // Метод для получения примеров работ категории
    public function edit($slug)
    {
        $category = \App\Models\Category::where('slug', $slug)->firstOrFail();
        $workExamples = WorkExample::where('category_id', $category->id)
            ->whereNull('subcategory_id')
            ->get();

        return response()->json(['workExamples' => $workExamples]);
    }

    // Метод для удаления изображения
    public function destroy($id)
    {
        $workExample = WorkExample::findOrFail($id);

        // Удаление файла изображения
        if ($workExample->image) {
            Storage::disk('public')->delete($workExample->image);
        }

        // Удаление файла миниатюры
        if ($workExample->thumb) {
            Storage::disk('public')->delete($workExample->thumb);
        }

        // Удаление записи из базы данных
        $workExample->delete();

        return response()->json(['success' => true]);
    }
}
