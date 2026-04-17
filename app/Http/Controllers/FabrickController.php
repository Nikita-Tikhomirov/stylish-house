<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Fabric;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;


class FabrickController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Fabric::query();



        // Поиск по названию
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }
        $fabricks = $query->paginate(20)->appends($request->query());

        if ($request->ajax()) {
            return response()->json([
                'fabricks' => view('admin.partials.fabricks', compact('fabricks'))->render(),
                'pagination' => (string) $fabricks->withQueryString()->links(),
            ]);
        }

        // $fabricks = Fabric::all();

        return view('admin.fabricks', compact('fabricks'));

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.createFabric');

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:fabrics,name',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $fabric = new Fabric();
        $fabric->name = $request->name;

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('fabrics', 'public');
            $fabric->image = $imagePath;
        }

        $fabric->save();

        return response()->json(['success' => 'Ткань успешно добавлена.']);
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Fabric $fabric)
    {
        return view('admin.fabricEdit', compact('fabric'));
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Fabric $fabric)
    {
        // Валидация данных
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            // 'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Обновление названия
        $fabric->name = $validated['name'];

        // Обновление изображения
        if ($request->hasFile('image')) {
            // Удаление старого файла, если он существует
            if ($fabric->image && Storage::exists($fabric->image)) {
                Storage::delete($fabric->image);
            }

            // Сохранение нового файла
            $fabric->image = $request->file('image')->store('fabrics', 'public');
        }

        // Сохранение изменений
        $fabric->save();

        // Перенаправление с сообщением об успехе
        return redirect()->route('admin.fabrics.index')->with('success', 'Ткань успешно обновлена.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $fabrick = Fabric::where('id', $id)->firstOrFail();
        $fabrick->delete();

        return response()->json(['success' => true, 'message' => 'Продукт успешно удалён']);
    }
}
