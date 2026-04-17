<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\User;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\ThrouElement;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

use App\Services\CartService;


class OrderController extends Controller
{
    protected $cartService;

    public function create(Request $request)
    {

        // Валидация данных из формы
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'secondname' => 'required|string|max:255',
            'addres' => 'nullable|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:255',
            'comment' => 'nullable|string',
            'items' => 'required|string' // JSON-строка с товарами
        ]);

        $cart = $request->session()->get('cart', []);

        if (empty($cart)) {
            return response()->json(['success' => false, 'message' => 'Корзина пуста'], 400);
        }


        // Создаем или получаем пользователя на основе email
        $user = User::firstOrCreate(
            ['email' => $validatedData['email']],
            [
                'name' => $validatedData['name'],
                'secondname' => $validatedData['secondname'],
                'addres' => $validatedData['addres'],
                'phone' => $validatedData['phone'],
                'password' => bcrypt('defaultpassword'), // Вы можете изменить на рандомный или другой пароль
                'role' => 'user'
            ]
        );

        // Преобразуем JSON-строку с товарами в массив
        $items = json_decode($validatedData['items'], true);

        // $totalPrice = array_reduce($items, function ($total, $item) {
        //     return $total + ($item['price'] * $item['quantity']);
        // }, 0);

        // Создаем заказ
        // Рассчитываем общую стоимость заказа
        $totalPrice = array_reduce($cart, function ($total, $item) {
            return $total + $item['price'];
        }, 0);

        // Сохраняем заказ в базе данных
        $order = Order::create([
            'user_id' => $user->id,
            'items' => json_encode($cart), // Сохраняем корзину в JSON-формате
            'total_price' => $totalPrice,
            'comment' => $validatedData['comment'] ?? null,
        ]);
        $request->session()->forget('cart');
        Auth::login($user);
        $deliveryCost = session('delivery_cost', 0); // По умолчанию 0, если не выбрана доставка
        if ($deliveryCost == 700) {
            // если доставка уже оплачена (700), прибавляем ещё 700
             $totalPrice += $deliveryCost;
        }
        try {
            $data = [
                'name' => $validatedData['name'],
                'secondname' => $validatedData['secondname'],
                'addres' => $validatedData['addres'],
                'phone' => $validatedData['phone'],
                'email' => $validatedData['email'],
                'comment' => $validatedData['comment'],
                'cart' => $cart, // передаем массив товаров
                'totalPrice' => $totalPrice,
                'delivery' => $deliveryCost,
            ];


            Mail::send('emails.cart', $data, function ($m) {
                $m->to('info@stylish-house.net')->subject('Заказ с сайта');
            });

            // return response()->json(['success' => true]);
            $redirectUrl = route('profile.show', ['id' => $user->id]); // Ссылка с ID пользователя
            return response()->json([
                'success' => true,
                'order_id' => $order->id,
                'redirect_url' => $redirectUrl
            ]);

        } catch (\Throwable $e) {
            \Log::error('Mail error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }



        // Перенаправляем на страницу успеха или обратно с сообщением


    }


    public function show(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $orders = $user->orders;
        // Проверяем, соответствует ли пользователь текущему
        if (Auth::id() !== $user->id) {
            abort(403); // Ошибка доступа, если пользователь не совпадает
        }
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
        $headerInfo = ThrouElement::first();


        $cart = $request->session()->get('cart', []);
        $curtainSubcats = Subcategory::whereIn('id', $headerInfo->curtain_subcategories ?? [])->with('category')->get();
        $blindSubcats = Subcategory::whereIn('id', $headerInfo->blind_subcategories ?? [])->with('category')->get();

        return view('admin.user', compact('user', 'categoriesInCatalogMenu', 'categoriesInHeaderMenu', 'orders', 'cart', 'headerInfo', 'curtainSubcats', 'blindSubcats'));


    }
}
