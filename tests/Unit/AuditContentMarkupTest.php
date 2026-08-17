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

    private const PRICE_CALCULATOR_TEMPLATES = [
        'front/home.blade.php',
        'front/category.blade.php',
        'front/categoryrolstavni.blade.php',
        'front/subcategory.blade.php',
        'front/subcategory-plumbing.blade.php',
        'front/subcategory-template-1.blade.php',
        'front/product.blade.php',
        'front/product-plumbing.blade.php',
        'front/cart.blade.php',
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

    public function test_product_price_requests_do_not_replace_errors_with_zero(): void
    {
        foreach (self::PRICE_CALCULATOR_TEMPLATES as $template) {
            $markup = $this->template($template);

            $this->assertStringNotContainsString('data.price || 0', $markup, $template);
            $this->assertStringContainsString('response.ok', $markup, $template);
        }
    }

    public function test_home_action_cards_expose_separate_cart_and_quick_view_controls(): void
    {
        $markup = preg_replace(
            '/{{--.*?--}}/s',
            '',
            $this->template('components/front/section/actions.blade.php')
        );

        $this->assertStringContainsString('bigProdCard__cart control quickProd', $markup);
        $this->assertStringContainsString('fas fa-cart-arrow-down', $markup);
        $this->assertStringContainsString('bigProdCard__quckView control quickProd', $markup);
        $this->assertStringContainsString('fas fa-eye', $markup);
    }

    public function test_product_popup_does_not_add_a_second_close_button_inside_its_content(): void
    {
        $markup = $this->template('components/front/popups.blade.php');
        $componentStyles = file_get_contents(__DIR__.'/../../resources/css/front-components.css');

        $this->assertStringNotContainsString('prodPopup__close', $markup);
        $this->assertStringNotContainsString('.modal:has(#popupProd)', $componentStyles);
        $this->assertStringNotContainsString('.prodPopup__close', $componentStyles);
    }

    public function test_home_markup_avoids_known_validator_regressions(): void
    {
        $head = $this->template('components/front/head.blade.php');
        $header = $this->template('components/front/header.blade.php');
        $home = $this->template('front/home.blade.php');
        $forms = $this->template('components/front/popups.blade.php')
            .$this->template('components/front/section/how.blade.php')
            .$home;
        $componentStyles = file_get_contents(__DIR__.'/../../resources/css/front-components.css');

        $this->assertStringNotContainsString('<noscript>', $head);
        $this->assertStringNotContainsString('user-scalable=no', $head);
        $this->assertStringContainsString('resources/css/front-components.css', $head);
        $this->assertSame(1, preg_match_all('/<style\b/i', $header));
        $this->assertSame(0, preg_match_all('/<label\b[^>]*>\s*<p\b/is', $forms));
        $this->assertSame(0, preg_match_all('/<textarea\b[^>]*\btype=/i', $forms));
        $this->assertSame(1, preg_match_all('/<meta\b[^>]*name="csrf-token"/i', $head.$home));
        $this->assertStringNotContainsString('::hover', $componentStyles);
    }

    public function test_card_tooltips_use_inline_markup_inside_buttons(): void
    {
        $templates = [
            'components/front/section/actions.blade.php',
            'components/front/section/populars.blade.php',
        ];

        foreach ($templates as $template) {
            $markup = $this->template($template);

            $this->assertStringNotContainsString('<div class="bigProdCard__toolTip">', $markup, $template);
            $this->assertStringContainsString('<span class="bigProdCard__toolTip">', $markup, $template);
        }
    }

    private function template(string $path): string
    {
        return file_get_contents(__DIR__.'/../../resources/views/'.$path);
    }
}
