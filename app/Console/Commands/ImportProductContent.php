<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Models\Tab;
use Illuminate\Support\Str;

class ImportProductContent extends Command
{
    protected $signature = 'import:product-content';
    protected $description = 'Импорт контента для товаров';

    public function handle()
    {
        $products = Product::where('model_id', '33')->get();

        foreach ($products as $product) {

            $prodTitle = $product->h1;
            // $metaTitleFinal = str_replace('[название товара]', $fullTitle, $meataTitle);

            Tab::create([
                'title' => 'Название вкладки',  // Задай нужное название
                'tab' => 'Содержимое вкладки',  // Здесь добавь контент
                'product_id' => $product->id,
            ]);

            // $product->content = 'Новый контент';
            // $product->save();
        }

        $this->info('Контент успешно добавлен в товары.');
    }

}
