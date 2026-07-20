<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Product;
use App\Models\ProdModel;
use App\Models\ThrouElement;
use App\Support\CartItemNormalizer;
use Illuminate\Support\Facades\Log;

class CartController extends Controller
{
    protected $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    /**
     * Отобразить корзину.
     */

    public function addToCart(Request $request, CartItemNormalizer $normalizer)
    {
        $validated = $request->validate([
            'productId' => ['required', 'integer', 'exists:products,id'],
            'width' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'height' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'control' => ['nullable'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
            'price' => ['required', 'numeric', 'min:0'],
            'side' => ['nullable', 'string', 'max:160'],
            'widthType' => ['nullable', 'string', 'max:160'],
            'controlColor' => ['nullable', 'string', 'max:160'],
            'configuration' => ['nullable', 'array'],
        ]);

        $product = Product::find($validated['productId']);
        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Товар не найден'], 404);
        }

        $item = $normalizer->normalize($validated, $product->h1 ?: $product->title);
        $uniqueKey = $normalizer->key($item);

        // Получаем текущую корзину из сессии
        $cart = $request->session()->get('cart', []);

        // Проверяем, есть ли товар с таким же уникальным ключом
        if (isset($cart[$uniqueKey])) {
            $cart[$uniqueKey]['quantity'] += $item['quantity'];
            $cart[$uniqueKey]['price'] += $item['price'];
        } else {
            $cart[$uniqueKey] = $item;
        }

        // Сохраняем обновленную корзину в сессии
        $request->session()->put('cart', $cart);

        $totalCount = array_sum(array_column($cart, 'quantity'));

        return response()->json([
            'success' => true,
            'message' => 'Товар добавлен в корзину',
            'cart_count' => $totalCount,
        ]);
    }




    public function show(Request $request)
    {
        $cart = $request->session()->get('cart', []); // Извлекаем данные корзины из сессии
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
        // Собираем уникальные ID товаров
        $productIds = array_column($cart, 'productId');
        $products = Product::whereIn('id', $productIds)->get();
        $headerInfo = ThrouElement::firstOrFail();
        // dd($products);
        $curtainSubcats = Subcategory::whereIn('id', $headerInfo->curtain_subcategories ?? [])->with('category')->get();
        $blindSubcats = Subcategory::whereIn('id', $headerInfo->blind_subcategories ?? [])->with('category')->get();




        return view('front.cart', compact('cart', 'categoriesInCatalogMenu', 'categoriesInHeaderMenu', 'products', 'headerInfo', 'curtainSubcats', 'blindSubcats'));
    }


    public function getCartItem(Request $request, $key)
    {
        $cart = $request->session()->get('cart', []);

        if (isset($cart[$key])) {
            $item = $cart[$key];
            $product = Product::find($item['productId']);
            $model = $product->model_id ? ProdModel::find($product->model_id) : null;

            return response()->json([
                'title' => $product->h1,
                'description' => $product->first_screenn_description,
                'image' => $product->image_path,
                'width' => $item['width'],
                'height' => $item['height'],
                'quantity' => $item['quantity'],
                'control' => $item['control'] ?? null,
                'model' => $model?->title,
                'cloth' => $product->cloth,
                'discount' => $product->discount,
                'side' => $item['side'] ?? null,
                'widthType' => $item['widthType'] ?? null,
                'controlColor' => $item['controlColor'] ?? null,
                'configuration' => $item['configuration'] ?? [],

            ]);
        }

        return response()->json(['error' => 'Товар не найден'], 404);
    }


    public function updateCartItem(Request $request)
    {
        $key = $request->input('key');
        $width = $request->input('width');
        $height = $request->input('height');
        $control = $request->input('control');
        $quantity = (int) $request->input('quantity');
        $price = $request->input('price');

        $cart = $request->session()->get('cart', []);


        if (isset($cart[$key])) {
            $cart[$key]['width'] = $width;
            $cart[$key]['height'] = $height;
            $cart[$key]['control'] = $control;
            $cart[$key]['quantity'] = $quantity;
            $cart[$key]['price'] = $price;


            $request->session()->put('cart', $cart);

            $totalCount = array_sum(array_column($cart, 'quantity'));

            return response()->json([
                'success' => true,
                'message' => 'Товар обновлен',
                'cart_count' => $totalCount,
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Товар не найден']);
    }



    public function removeFromCart(Request $request)
    {
        $key = $request->input('key'); // Уникальный ключ товара в корзине

        // Получаем текущую корзину из сессии
        $cart = $request->session()->get('cart', []);

        if (isset($cart[$key])) {
            unset($cart[$key]); // Удаляем товар из корзины
            $request->session()->put('cart', $cart); // Обновляем корзину в сессии

            $totalCount = array_sum(array_column($cart, 'quantity'));

            return response()->json([
                'success' => true,
                'message' => 'Товар удален из корзины',
                'cart_count' => $totalCount,
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Товар не найден']);
    }

    public function updateDelivery(Request $request)
    {
        $deliveryCost = $request->input('deliveryCost');
        $request->session()->put('delivery_cost', $deliveryCost);

        // Получаем текущую корзину из сессии
        $cart = $request->session()->get('cart', []);

        // Вычисляем общую стоимость товаров в корзине
        $subtotal = 0;
        foreach ($cart as $item) {
            $subtotal += $item['price'];
        }

        // Вычисляем итоговую стоимость с учетом доставки
        $totalPrice = $subtotal + (float) $deliveryCost;

        return response()->json(['success' => true, 'totalPrice' => $totalPrice, 'message' => 'Стоимость доставки обновлена']);
    }



}
