<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProdModel;


class ModelsController extends Controller
{
    public function index()
    {
        $models = ProdModel::all(); // Get all categories
        return view('admin.models', compact('models'));

    }
    public function create()
    {
        return view('admin.modelCreate');
    }

    public function edit($id)
    {
        $model = ProdModel::where('id', $id)->firstOrFail();
        return view('admin.modelEdit', compact('model'));
    }


    public function update(Request $request, $id)
    {
        $model = ProdModel::findOrFail($id);
        $model->title = $request->title;
        $model->h1 = $request->h1;

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('models', 'public');
            $model->image = $imagePath;
        }

        $model->save();

        return response()->json([
            'success' => true,
            'message' => 'Модель обновлена',
            'image_url' => asset('storage/' . $model->image)
        ]);
    }


    public function store(Request $request)
    {


        // $request->validate([
        //     'title' => 'required|string|max:255',
        //     'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
        //     'mask_coordinates' =>'required|string|max:255'

        // ]);
        $model = ProdModel::create([
            'title' => $request->title,
            'h1' => $request->h1,
            // 'mask_coordinates' => $request->mask_coordinates,
        ]);
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('models', 'public');
            $model->image = $imagePath;
            $model->save();
        }

        return redirect()->route('model.index');

    }


    public function destroy($id)
    {
        // Найти подкатегорию по слагу
        $model = ProdModel::findOrFail($id);

        // Удалить подкатегорию
        $model->delete();

        // Вернуть пустой успешный ответ
        return response()->noContent();
    }
}

