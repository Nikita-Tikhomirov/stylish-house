<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Faq;
use App\Models\Category;
use App\Models\Subcategory;



class FaqController extends Controller
{
    public function store(Request $request, $slug)
    {
        $category = Category::where('slug', $slug)->firstOrFail();
        $subcategorySlug = $request->input('subcategory_slug');
        $subcategory = $subcategorySlug ? Subcategory::where('slug', $subcategorySlug)->where('category_id', $category->id)->first() : null;

        $faq = Faq::create([
            'category_id' => $category->id,
            'subcategory_id' => $subcategory ? $subcategory->id : null,
            'question' => $request->input('question'),
            'answer' => $request->input('answer')
        ]);

        return response()->json(['faq' => $faq, 'message' => 'Вопрос успешно добавлен']);
    }

    public function update(Request $request, $slug, $id)
    {
        $category = Category::where('slug', $slug)->firstOrFail();
        $subcategorySlug = $request->input('subcategory_slug');
        $subcategory = $subcategorySlug ? Subcategory::where('slug', $subcategorySlug)->where('category_id', $category->id)->first() : null;

        $faq = Faq::findOrFail($id);

        $faq->update([
            'question' => $request->input('question'),
            'answer' => $request->input('answer'),
            'subcategory_id' => $subcategory ? $subcategory->id : null
        ]);

        return response()->json(['message' => 'Вопрос успешно обновлен']);
    }
    public function destroy($slug, $id)
    {
        $faq = Faq::findOrFail($id);
        $faq->delete();

        return response()->json(['message' => 'Вопрос успешно удален']);
    }
}
