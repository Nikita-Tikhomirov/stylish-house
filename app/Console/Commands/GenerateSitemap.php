<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Models\Subcategory;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate';

    protected $description = 'Генерация карты сайта';

    public function handle()
    {
        $today = Carbon::today();
        $sitemap = Sitemap::create()
            ->add(Url::create($this->canonicalUrl(route('front.home')))->setLastModificationDate($today))
            ->add(Url::create($this->canonicalUrl(route('policy')))->setLastModificationDate($today));

        foreach (Category::query()->where('show_in_catalog', true)->get() as $category) {
            $sitemap->add(Url::create($this->canonicalUrl(route('category.show', ['slug' => $category->slug])))
                ->setLastModificationDate($today));
        }

        foreach (Subcategory::query()
            ->where('show_in_catalog', true)
            ->whereHas('category', fn ($query) => $query->where('show_in_catalog', true))
            ->with('category:id,slug')
            ->get() as $subcategory) {
            $sitemap->add(Url::create($this->canonicalUrl(route('subcategory.show', [
                'category_slug' => $subcategory->category->slug,
                'subcategory_slug' => $subcategory->slug,
            ])))->setLastModificationDate($today));
        }

        foreach (Product::query()
            ->where('show_in_catalog', true)
            ->whereHas('category', fn ($query) => $query->where('categories.show_in_catalog', true))
            ->whereHas('subcategory', fn ($query) => $query
                ->where('subcategories.show_in_catalog', true)
                ->whereColumn('subcategories.category_id', 'products.category_id'))
            ->with(['category:id,slug', 'subcategory:id,slug,category_id'])
            ->get() as $product) {
            $sitemap->add(Url::create($this->canonicalUrl(route('product.show', [
                'category_slug' => $product->category->slug,
                'subcategory_slug' => $product->subcategory->slug,
                'product_slug' => $product->slug,
            ])))->setLastModificationDate($today));
        }

        foreach (Page::query()->get() as $page) {
            $sitemap->add(Url::create($this->canonicalUrl(route('pages.index', ['slug' => $page->slug])))
                ->setLastModificationDate($page->updated_at ?? $today));
        }

        $sitemap->writeToFile(public_path('sitemap.xml'));

        $this->info('Карта сайта создана!');

        return self::SUCCESS;
    }

    private function canonicalUrl(string $url): string
    {
        return Str::finish($url, '/');
    }
}
