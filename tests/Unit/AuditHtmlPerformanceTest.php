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
}
