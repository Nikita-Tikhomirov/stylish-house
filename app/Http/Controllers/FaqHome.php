<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HomePageFaq;
use Illuminate\Support\Facades\Validator;


class FaqHome extends Controller
{
    // Создание новой FAQ карточки
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'text' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $faq = HomePageFaq::create($request->all());

        return response()->json(['success' => 'Вопрос успешно создан', 'faq' => $faq]);
    }

    // Обновление существующей FAQ карточки
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'text' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $faq = HomePageFaq::findOrFail($id);
        $faq->update($request->all());

        return response()->json(['success' => 'Вопрос успешно обновлен', 'faq' => $faq]);
    }

    // Удаление FAQ карточки
    public function destroy($id)
    {
        $faq = HomePageFaq::findOrFail($id);
        $faq->delete();

        return response()->json(['success' => 'Вопрос успешно удален']);
    }
}
