<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ThrouElement;

class HeaderInfo extends Controller
{
    /**
     * Показать форму редактирования информации.
     */
    public function edit()
    {
        // Получить текущую запись из базы данных
        $headerInfo = ThrouElement::first();

        // Возвращаем вид с формой
        return view('admin.headerInfo', compact('headerInfo'));
    }

    /**
     * Обновить информацию.
     */
    public function update(Request $request)
    {
        // Валидация входных данных
        $request->validate([
            'logo_color' => 'nullable|string|max:255',
            'working_hours' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
        ]);

        // Найти или создать запись
        $headerInfo = ThrouElement::firstOrCreate([]);

        // Обновить данные
        $headerInfo->update($request->only(['logo_color', 'working_hours', 'phone_number', 'address']));

        // Перенаправление с сообщением
        return redirect()->back()->with('success', 'Информация успешно обновлена.');
    }
}
