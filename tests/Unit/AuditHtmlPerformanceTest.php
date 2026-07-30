<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class AuditHtmlPerformanceTest extends TestCase
{
    public function test_shared_logo_markup_does_not_contain_script_elements(): void
    {
        foreach (['header.blade.php', 'footer.blade.php'] as $file) {
            $source = file_get_contents(__DIR__.'/../../resources/views/components/front/'.$file);

            $this->assertStringNotContainsString('<script xmlns="" />', $source, $file);
        }
    }

    public function test_roller_shutter_category_does_not_load_swiper_twice(): void
    {
        $source = file_get_contents(__DIR__.'/../../resources/views/front/categoryrolstavni.blade.php');

        $this->assertStringContainsString("@vite('resources/js/swiper.js')", $source);
        $this->assertStringNotContainsString('swiper@11/swiper-bundle.min.js', $source);
    }

    public function test_below_fold_media_images_are_lazy_loaded(): void
    {
        $files = [
            'resources/views/components/front/section/gallery.blade.php',
            'resources/views/components/front/section/subgallery.blade.php',
            'resources/views/components/front/section/site-portfolio.blade.php',
            'resources/views/components/front/section/videorevs.blade.php',
            'resources/views/components/front/section/subvideorevs.blade.php',
            'resources/views/components/front/section/populars.blade.php',
            'resources/views/components/front/section/subcatProducts.blade.php',
            'resources/views/components/front/section/subcats.blade.php',
            'resources/views/front/partials/products.blade.php',
            'resources/views/front/partials/catproducts.blade.php',
        ];

        foreach ($files as $file) {
            $source = file_get_contents(__DIR__.'/../../'.$file);
            $sourceWithoutBladeArrows = str_replace('->', '__BLADE_ARROW__', $source);
            preg_match_all('/<img\b[^>]*>/is', $sourceWithoutBladeArrows, $matches);

            $this->assertNotEmpty($matches[0], $file);

            foreach ($matches[0] as $image) {
                $this->assertStringContainsString('loading="lazy"', $image, $file);
                $this->assertStringContainsString('decoding="async"', $image, $file);
            }
        }
    }

    public function test_mobile_content_is_constrained_to_the_viewport(): void
    {
        $css = file_get_contents(__DIR__.'/../../resources/css/main.css');

        $this->assertStringContainsString('.header__bottomMenu {', $css);
        $this->assertStringContainsString('overflow-wrap: anywhere;', $css);
        $this->assertStringContainsString('.breadcrumbs {', $css);
        $this->assertStringContainsString('overflow-x: auto;', $css);
    }

    public function test_yandex_map_is_loaded_by_the_lazy_frontend_module(): void
    {
        $map = file_get_contents(__DIR__.'/../../resources/views/components/front/section/map.blade.php');
        $shop = file_get_contents(__DIR__.'/../../resources/js/shop.js');

        $this->assertStringContainsString('data-yandex-map', $map);
        $this->assertStringNotContainsString('api-maps.yandex.ru', $map);
        $this->assertStringNotContainsString('<script', $map);
        $this->assertStringContainsString("import './lazy-yandex-map.js';", $shop);
    }

    public function test_google_fonts_are_connected_once_without_css_imports(): void
    {
        $css = file_get_contents(__DIR__.'/../../resources/css/main.css');
        $head = file_get_contents(__DIR__.'/../../resources/views/components/front/head.blade.php');

        $this->assertStringNotContainsString('@import url', $css);
        $this->assertSame(1, substr_count($head, 'fonts.googleapis.com/css2'));
        $this->assertStringContainsString('rel="preconnect" href="https://fonts.googleapis.com"', $head);
        $this->assertStringContainsString('rel="preconnect" href="https://fonts.gstatic.com" crossorigin', $head);
    }

    public function test_legacy_product_menu_queries_are_removed(): void
    {
        $header = file_get_contents(__DIR__.'/../../resources/views/components/front/header.blade.php');
        $this->assertStringNotContainsString('categoriesInCatalogMenu', $header);
        $this->assertStringNotContainsString('categoriesInHeaderMenu', $header);
        $this->assertStringNotContainsString('@foreach ($subcategory->products', $header);

        $controllers = [
            'app/Http/Controllers/CartController.php',
            'app/Http/Controllers/CheckoutController.php',
            'app/Http/Controllers/CategoryController.php',
            'app/Http/Controllers/HomeController.php',
            'app/Http/Controllers/OrderController.php',
            'app/Http/Controllers/PageController.php',
            'app/Http/Controllers/ProductController.php',
            'app/Http/Controllers/SubcategoryController.php',
            'app/Http/Controllers/Auth/LoginController.php',
            'app/Http/Controllers/Auth/RegisterController.php',
        ];

        foreach ($controllers as $controller) {
            $source = file_get_contents(__DIR__.'/../../'.$controller);
            $this->assertStringNotContainsString('categoriesInCatalogMenu', $source, $controller);
            $this->assertStringNotContainsString('categoriesInHeaderMenu', $source, $controller);
        }
    }
}
