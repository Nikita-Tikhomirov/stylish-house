<?php

namespace App\Http\Controllers;

use App\Services\ProductMinPriceCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ExcelController extends Controller
{
    public function __construct(
        private readonly ProductMinPriceCalculator $calculator
    ) {
    }

    public function test()
    {
        $sheetKeys = Cache::get('cached_sheets', []);
        return response()->json(['cached_sheets' => $sheetKeys]);
    }

    public function getProdPrice(Request $request)
    {
        $result = $this->calculator->calculate([
            'model' => $request->input('model'),
            'cloth' => $request->input('cloth'),
            'control' => $request->input('control'),
            'modelId' => $request->input('modelId'),
            'prodTitle' => $request->input('prodTitle'),
            'width' => $request->input('width'),
            'height' => $request->input('height'),
        ]);

        if ($result['price'] !== null) {
            return response()->json(['price' => $result['price']]);
        }

        if ($result['error'] === ProductMinPriceCalculator::ERROR_SHEET_NOT_FOUND) {
            return response()->json(['error' => 'Sheet not found'], 404);
        }

        return response()->json(['price' => 'Цена по запросу ']);
    }
}
