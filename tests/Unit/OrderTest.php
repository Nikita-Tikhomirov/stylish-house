<?php

namespace Tests\Unit;

use App\Models\Order;
use PHPUnit\Framework\TestCase;

class OrderTest extends TestCase
{
    public function test_it_normalizes_legacy_string_items(): void
    {
        $order = new Order();
        $order->setRawAttributes(['items' => '"Legacy product"']);

        $this->assertSame(
            [['productName' => 'Legacy product']],
            $order->normalized_items
        );
    }

    public function test_it_preserves_current_item_arrays(): void
    {
        $items = [[
            'productName' => 'Roller shutter',
            'quantity' => 2,
            'price' => 15000,
        ]];
        $order = new Order();
        $order->setRawAttributes(['items' => json_encode($items)]);

        $this->assertSame($items, $order->normalized_items);
    }
}
