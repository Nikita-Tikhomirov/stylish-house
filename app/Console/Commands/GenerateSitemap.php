<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;
use App\Models\Product;
use App\Models\Category;
use App\Models\Subcategory;
use Carbon\Carbon;

class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate';
    protected $description = 'Генерация карты сайта';

    public function handle()
    {
        $sitemap = Sitemap::create()
            ->add(Url::create(route('front.home'))->setLastModificationDate(Carbon::today()))
            ->add(Url::create(route('cart.show'))->setLastModificationDate(Carbon::today()));
            // ->add(Url::create(route('catalog'))->setLastModificationDate(Carbon::today()));
            // ->add(Url::create(route('contact'))->setLastModificationDate(Carbon::today()));

            foreach (Category::all() as $category) {
                $sitemap->add(Url::create(route('category.show', ['slug' => $category->slug]))
                    ->setLastModificationDate(Carbon::today()));
            }

            foreach (Subcategory::all() as $subcategory) {
                $sitemap->add(Url::create(route('subcategory.show', [
                    'category_slug' => $subcategory->category->slug,
                    'subcategory_slug' => $subcategory->slug
                ]))->setLastModificationDate(Carbon::today()));
            }

            foreach (Product::all() as $product) {
                if ($product->category && $product->subcategory) {
                    $sitemap->add(Url::create(route('product.show', [
                        'category_slug' => $product->category->slug,
                        'subcategory_slug' => $product->subcategory->slug,
                        'product_slug' => $product->slug
                    ]))->setLastModificationDate(Carbon::today()));
                }
            }

        $sitemap->writeToFile(public_path('sitemap.xml'));

        $this->info('Карта сайта создана!');
    }
}