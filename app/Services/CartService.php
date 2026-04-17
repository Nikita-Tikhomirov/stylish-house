<?php

namespace App\Services;

class CartService
{
    protected $sessionKey = 'cart';

    public function addToCart($productId, $quantity = 1)
    {
        $cart = session()->get($this->sessionKey, []);

        if (isset($cart[$productId])) {
            $cart[$productId] += $quantity;
        } else {
            $cart[$productId] = $quantity;
        }

        session()->put($this->sessionKey, $cart);
    }

    public function getCart()
    {
        return session()->get($this->sessionKey, []);
    }
}
