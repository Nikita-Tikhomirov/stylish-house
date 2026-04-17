<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Subcategory;
use App\Services\CartService;
use App\Models\Product;
use App\Models\ThrouElement;
class CheckoutController extends Controller
{
    /**
     * Display a listing of the resource.
     */


    protected $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    public function index(Request $request)
    {
        $cart = $request->session()->get('cart', []);
        // Получаем категории, подкатегории и товары, где show_in_catalog = true
        $categoriesInCatalogMenu = Category::where('show_in_catalog', true)
            ->with([
                'subcategories' => function ($query) {
                    $query->where('show_in_catalog', true)
                        ->with([
                            'products' => function ($query) {
                                $query->where('show_in_catalog', true);
                            }
                        ]);
                }
            ])
            ->get();

        $categoriesInHeaderMenu = Category::where('show_in_menu', true)
            ->with([
                'subcategories' => function ($query) {
                    $query->where('show_in_menu', true)
                        ->with([
                            'products' => function ($query) {
                                $query->where('show_in_menu', true);
                            }
                        ]);
                }
            ])
            ->get();
        $productIds = array_map(function ($item) {
            return $item['productId'];
        }, $cart); // Извлекаем productId из всех элементов корзины

        // Загружаем товары, которые есть в корзине
        $headerInfo = ThrouElement::firstOrFail();
        $products = Product::whereIn('id', $productIds)->get();
        $curtainSubcats = Subcategory::whereIn('id', $headerInfo->curtain_subcategories ?? [])->with('category')->get();
        $blindSubcats = Subcategory::whereIn('id', $headerInfo->blind_subcategories ?? [])->with('category')->get();
        // Получение стоимости доставки из сессии
        $deliveryCost = session('delivery_cost', 0); // По умолчанию 0, если не выбрана доставка

        return view('front.checkout', compact('categoriesInCatalogMenu', 'categoriesInHeaderMenu', 'cart', 'products', 'headerInfo', 'curtainSubcats', 'blindSubcats', 'deliveryCost'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
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
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
