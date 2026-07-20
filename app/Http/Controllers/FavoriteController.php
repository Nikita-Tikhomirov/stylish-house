<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'product_ids' => $request->user()->favoriteProducts()->pluck('products.id')->values(),
        ]);
    }

    public function store(Request $request, Product $product): JsonResponse
    {
        $request->user()->favoriteProducts()->syncWithoutDetaching([$product->id]);

        return response()->json(['favorite' => true, 'product_id' => $product->id]);
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        $request->user()->favoriteProducts()->detach($product->id);

        return response()->json(['favorite' => false, 'product_id' => $product->id]);
    }

    public function sync(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_ids' => ['present', 'array', 'max:500'],
            'product_ids.*' => ['integer'],
        ]);

        $productIds = Product::query()
            ->whereIn('id', $validated['product_ids'])
            ->pluck('id')
            ->all();

        $request->user()->favoriteProducts()->syncWithoutDetaching($productIds);

        return $this->index($request);
    }
}
