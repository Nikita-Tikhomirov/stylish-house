<?php

namespace App\Http\Controllers;

use App\Models\IconCard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class IconCardController extends Controller
{
    // Создание новой карточки
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'icon_class' => 'required|string',
            'title' => 'required|string|max:255',
            'text' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $iconCard = IconCard::create($request->all());

        return response()->json(['success' => 'Карточка успешно создана', 'iconCard' => $iconCard]);
    }

    // Обновление существующей карточки
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'icon_class' => 'required|string',
            'title' => 'required|string|max:255',
            'text' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $iconCard = IconCard::findOrFail($id);
        $iconCard->update($request->all());

        return response()->json(['success' => 'Карточка успешно обновлена', 'iconCard' => $iconCard]);
    }

    // Удаление карточки
    public function destroy($id)
    {
        $iconCard = IconCard::findOrFail($id);
        $iconCard->delete();

        return response()->json(['success' => 'Карточка успешно удалена']);
    }
}
