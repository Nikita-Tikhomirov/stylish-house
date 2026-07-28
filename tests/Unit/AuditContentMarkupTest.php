<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class AuditContentMarkupTest extends TestCase
{
    private const H1_TEMPLATES = [
        'front/category.blade.php',
        'front/categoryrolstavni.blade.php',
        'front/subcategory.blade.php',
        'front/subcategory-plumbing.blade.php',
        'front/subcategory-template-1.blade.php',
        'front/product.blade.php',
        'front/product-plumbing.blade.php',
        'front/pages.blade.php',
    ];

    private const READ_MORE_TEMPLATES = [
        'front/category.blade.php',
        'front/subcategory.blade.php',
        'front/subcategory-template-1.blade.php',
        'front/product.blade.php',
        'front/product-plumbing.blade.php',
    ];

    public function test_public_templates_have_exactly_one_h1(): void
    {
        foreach (self::H1_TEMPLATES as $template) {
            $markup = $this->template($template);
            $markup = preg_replace('/{{--.*?--}}/s', '', $markup);

            $this->assertSame(
                1,
                preg_match_all('/<h1\b/i', $markup),
                "Expected exactly one H1 in {$template}"
            );
        }
    }

    public function test_home_uses_the_existing_seo_heading_instead_of_a_slider_heading(): void
    {
        $hero = $this->template('components/front/section/hero.blade.php');
        $home = $this->template('front/home.blade.php');

        $this->assertSame(0, preg_match_all('/<h1\b/i', $hero));
        $this->assertStringContainsString('<x-front.section.seo', $home);
    }

    public function test_product_summaries_use_accessible_read_more_component(): void
    {
        foreach (self::READ_MORE_TEMPLATES as $template) {
            $markup = $this->template($template);

            $this->assertStringContainsString('<x-front.read-more', $markup, $template);
            $this->assertStringNotContainsString('<span class="more">', $markup, $template);
        }
    }

    public function test_known_visible_text_errors_are_absent(): void
    {
        $templates = [
            'components/front/popups.blade.php',
            'components/front/section/rollets-product-calculator.blade.php',
            'auth/login.blade.php',
            'auth/register.blade.php',
        ];

        foreach ($templates as $template) {
            $markup = $this->template($template);

            $this->assertStringNotContainsString('Коментарий', $markup, $template);
            $this->assertStringNotContainsString('что бы', $markup, $template);
            $this->assertStringNotContainsString('[object Object]', $markup, $template);
            $this->assertStringNotContainsString('Р ', $markup, $template);
        }
    }

    private function template(string $path): string
    {
        return file_get_contents(__DIR__.'/../../resources/views/'.$path);
    }
}
