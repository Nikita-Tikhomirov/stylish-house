<?php

namespace App\Http\Controllers;

use App\Models\SeoSection;
use Illuminate\Http\Request;

class SeoSectionController extends Controller
{
    /**
     * Display a listing of the resource.
     */


     public function updateTextEditorSection(Request $request)
     {

         $request->validate([
             'content' => 'required|string',
         ]);

         // Найдите или создайте экземпляр SeoSection
         $seoSection = SeoSection::first(); // Или используйте find() с нужным ID

         if (!$seoSection) {
             $seoSection = new SeoSection();
         }

         // Обновите контент
         $seoSection->content = $request->input('content');
         $seoSection->save();

         // Возврат успешного ответа
         return response()->json(['message' => 'Контент успешно обновлен!'], 200);
     }


}
