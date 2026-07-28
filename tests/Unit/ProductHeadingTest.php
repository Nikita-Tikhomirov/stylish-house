<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ProductHeadingTest extends TestCase
{
    /** @dataProvider productTemplateProvider */
    public function test_product_template_has_one_primary_heading(string $template): void
    {
        $contents = file_get_contents(dirname(__DIR__, 2) . '/resources/views/front/' . $template);

        $this->assertSame(1, preg_match_all('/<h1\b/i', $contents), $template . ' must contain exactly one h1.');
        $this->assertMatchesRegularExpression(
            '/<h1\s+class="prodMain__title title">\s*<span>\{\{\s*\$product->h1\s*\}\}/',
            $contents,
            $template . ' must use the product H1 as the primary page heading.'
        );
    }

    public static function productTemplateProvider(): array
    {
        return [
            'standard product' => ['product.blade.php'],
            'plumbing product' => ['product-plumbing.blade.php'],
        ];
    }
}
