<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Tab;



class TabsController extends Controller
{

    public function store(Request $request, $product_slug)
    {
        $product = Product::where('slug', $product_slug)->firstOrFail();

        $tab = Tab::create([
            'title' => $request->input('title'),
            'tab' => $request->input('tab'),
            'product_id' => $product->id,
        ]);
        return response()->json(['tab' => $tab, 'message' => 'Таб успешно добавлен']);

    }

    public function update(Request $request, $id)
    {

        $tab = Tab::findOrFail($id);

        $tab->update([
            'title' => $request->input('title'),
            'tab' => $request->input('tab'),
        ]);

        return response()->json(['message' => 'Таб успешно обновлен']);
    }

    public function destroy($id)
    {
        $tab = Tab::findOrFail($id);
        $tab->delete();

        return response()->json(['message' => 'Таб успешно удален']);
    }

}
