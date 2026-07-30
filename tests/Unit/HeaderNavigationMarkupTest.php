<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class HeaderNavigationMarkupTest extends TestCase
{
    public function test_navigation_component_is_semantic_accessible_and_image_free(): void
    {
        $path = dirname(__DIR__, 2).'/resources/views/components/front/header-navigation.blade.php';
        $contents = file_get_contents($path);

        $this->assertStringContainsString('data-header-navigation', $contents);
        $this->assertMatchesRegularExpression('/<button[^>]+data-navigation-toggle[^>]+aria-expanded="false"/s', $contents);
        $this->assertStringContainsString('data-navigation-panel', $contents);
        $this->assertStringContainsString('role="tablist"', $contents);
        $this->assertStringContainsString('role="tab"', $contents);
        $this->assertStringContainsString('data-navigation-search', $contents);
        $this->assertStringContainsString('data-mobile-accordion', $contents);
        $this->assertStringNotContainsString('<img', $contents);
    }

    public function test_header_uses_the_navigation_component_instead_of_legacy_product_trees(): void
    {
        $path = dirname(__DIR__, 2).'/resources/views/components/front/header.blade.php';
        $contents = file_get_contents($path);

        $this->assertStringContainsString('<x-front.header-navigation', $contents);
        $this->assertStringNotContainsString('@if (false)', $contents);
        $this->assertStringNotContainsString('categoriesInCatalogMenu', $contents);
        $this->assertStringNotContainsString('categoriesInHeaderMenu', $contents);
        $this->assertStringNotContainsString('@foreach ($subcategory->products', $contents);
    }
}
