<?php

namespace App\Http\Controllers;

use App\Mail\OrderCreatedMail;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\User;
use App\Models\Product;
use App\Models\Subcategory;
use App\Models\ThrouElement;
use App\Support\CartItemNormalizer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function create(Request $request, CartItemNormalizer $normalizer)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'secondname' => 'required|string|max:255',
            'addres' => 'nullable|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:255',
            'comment' => 'nullable|string|max:2000',
            'privacy_consent' => 'accepted',
        ]);

        $cart = $request->session()->get('cart', []);
        if (empty($cart)) {
            return response()->json(['success' => false, 'message' => 'Корзина пуста'], 400);
        }

        [$user, $newCustomer, $requiresLogin] = $this->resolveCustomer($validatedData);
        $items = $this->normalizeCart($cart, $normalizer);
        [$deliveryMethod, $deliveryCost] = $this->deliverySnapshot($request->session()->get('delivery_cost', 0));
        $totalPrice = array_sum(array_column($items, 'price')) + $deliveryCost;
        $customerDetails = $this->customerSnapshot($validatedData, $user);

        $order = DB::transaction(function () use (
            $user,
            $items,
            $totalPrice,
            $validatedData,
            $deliveryMethod,
            $deliveryCost,
            $customerDetails
        ) {
            return Order::create([
                'user_id' => $user->id,
                'items' => $items,
                'total_price' => $totalPrice,
                'comment' => $validatedData['comment'] ?? null,
                'delivery_method' => $deliveryMethod,
                'delivery_cost' => $deliveryCost,
                'customer_details' => $customerDetails,
            ]);
        });

        if ($newCustomer) {
            Auth::login($user);
        }

        $request->session()->forget(['cart', 'delivery_cost']);
        $this->sendOrderMail($order);

        return response()->json([
            'success' => true,
            'order_id' => $order->id,
            'requires_login' => $requiresLogin,
            'redirect_url' => Auth::check() ? route('profile.account') : route('login'),
        ]);
    }

    public function show(Request $request)
    {
        $user = $request->user();
        $orders = $user->orders()->latest()->get();
        $favoriteProducts = $user->favoriteProducts()
            ->with(['category', 'subcategory'])
            ->latest('favorites.created_at')
            ->get();
        $headerInfo = ThrouElement::first();


        $cart = $request->session()->get('cart', []);
        $curtainSubcats = Subcategory::whereIn('id', $headerInfo->curtain_subcategories ?? [])->with('category')->get();
        $blindSubcats = Subcategory::whereIn('id', $headerInfo->blind_subcategories ?? [])->with('category')->get();

        return view('admin.user', compact(
            'user',
            'orders',
            'favoriteProducts',
            'cart',
            'headerInfo',
            'curtainSubcats',
            'blindSubcats'
        ));
    }

    private function resolveCustomer(array $data): array
    {
        if (Auth::check()) {
            $user = Auth::user();
            $user->forceFill([
                'name' => $data['name'],
                'secondname' => $data['secondname'],
                'addres' => $data['addres'] ?? null,
                'phone' => $data['phone'],
            ])->save();

            return [$user, false, false];
        }

        $existingUser = User::where('email', $data['email'])->first();
        if ($existingUser) {
            return [$existingUser, false, true];
        }

        $user = User::create([
            'name' => $data['name'],
            'secondname' => $data['secondname'],
            'addres' => $data['addres'] ?? null,
            'phone' => $data['phone'],
            'email' => $data['email'],
            'password' => bcrypt(Str::random(48)),
        ]);

        return [$user, true, false];
    }

    private function normalizeCart(array $cart, CartItemNormalizer $normalizer): array
    {
        $productNames = Product::query()
            ->whereIn('id', array_column($cart, 'productId'))
            ->get()
            ->mapWithKeys(fn (Product $product) => [
                $product->id => $product->h1 ?: $product->title,
            ]);

        return array_values(array_map(function (array $item) use ($normalizer, $productNames) {
            $name = $productNames[$item['productId']] ?? ($item['productName'] ?? 'Товар');

            return $normalizer->normalize($item, $name);
        }, $cart));
    }

    private function deliverySnapshot(mixed $delivery): array
    {
        if ((string) $delivery === '700') {
            return ['courier_mkad', 700];
        }

        if ($delivery === 'delivery') {
            return ['courier_outside', 0];
        }

        return ['pickup', 0];
    }

    private function customerSnapshot(array $data, User $user): array
    {
        return [
            'name' => $data['name'],
            'secondname' => $data['secondname'],
            'addres' => $data['addres'] ?? null,
            'phone' => $data['phone'],
            'email' => Auth::check() ? $user->email : $data['email'],
        ];
    }

    private function sendOrderMail(Order $order): void
    {
        $this->sendOrderMailTo(
            config('mail.order_recipient'),
            new OrderCreatedMail($order, true),
            $order,
            'admin'
        );
        $this->sendOrderMailTo(
            data_get($order->customer_details, 'email'),
            new OrderCreatedMail($order),
            $order,
            'customer'
        );
    }

    private function sendOrderMailTo(
        ?string $recipient,
        OrderCreatedMail $mail,
        Order $order,
        string $recipientType
    ): void {
        if (!$recipient) {
            Log::error('Order notification recipient is missing', [
                'order_id' => $order->id,
                'recipient_type' => $recipientType,
            ]);

            return;
        }

        try {
            Mail::to($recipient)->send($mail);
        } catch (\Throwable $exception) {
            Log::error('Order notification failed', [
                'order_id' => $order->id,
                'recipient_type' => $recipientType,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
