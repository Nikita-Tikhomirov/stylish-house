<?php

namespace Tests\Unit;

use App\Services\CatalogImport\TemplateProductRewriter;
use PHPUnit\Framework\TestCase;

class TemplateProductRewriterTest extends TestCase
{
    public function test_public_copy_removes_donor_marketing_contacts_and_price(): void
    {
        $result = (new TemplateProductRewriter)->rewrite($this->source('11889'));

        $publicText = mb_strtolower(implode(' ', [
            $result->title,
            $result->summary,
            $result->description,
        ]));

        $this->assertStringNotContainsString('kortin', $publicText);
        $this->assertStringNotContainsString('rimskie', $publicText);
        $this->assertStringNotContainsString('https://', $publicText);
        $this->assertStringNotContainsString('+7 906', $publicText);
        $this->assertStringNotContainsString('2708', $publicText);
        $this->assertStringNotContainsString('лучш', $publicText);
        $this->assertStringNotContainsString('в исходных характеристиках', $publicText);
        $this->assertStringNotContainsString('текст сохраняет', $publicText);
        $this->assertStringNotContainsString('карточка модели', $publicText);
        $this->assertStringNotContainsString('в тексте не используются', $publicText);
        $this->assertStringContainsString('полиэстер', $publicText);
        $this->assertStringContainsString('бел', $publicText);
        $this->assertStringNotContainsString('хлопок', $publicText);
        $this->assertContains('removed_branding', $result->warnings);
        $this->assertGreaterThanOrEqual(25, mb_strlen($result->title));
        $this->assertLessThanOrEqual(140, mb_strlen($result->title));
        $this->assertGreaterThanOrEqual(80, mb_strlen($result->summary));
        $this->assertLessThanOrEqual(220, mb_strlen($result->summary));
        $this->assertGreaterThanOrEqual(180, mb_strlen($result->description));
        $this->assertLessThanOrEqual(1000, mb_strlen($result->description));
    }

    public function test_identical_input_produces_byte_stable_content(): void
    {
        $rewriter = new TemplateProductRewriter;

        $first = $rewriter->rewrite($this->source('11889'));
        $second = $rewriter->rewrite($this->source('11889'));

        $this->assertEquals($first, $second);
    }

    public function test_each_crc32_layout_is_distinct_and_keeps_the_same_factual_values(): void
    {
        $rewriter = new TemplateProductRewriter;
        $results = [];

        foreach (['4', '2', '5', '1'] as $externalId) {
            $result = $rewriter->rewrite($this->source($externalId));
            $results[] = $result->description;

            $publicText = mb_strtolower($result->summary.' '.$result->description);
            $this->assertStringContainsString('полиэстер', $publicText);
            $this->assertStringContainsString('бел', $publicText);
        }

        $this->assertCount(4, array_unique($results));
    }

    public function test_unknown_attribute_warnings_are_stable_sorted_and_not_rendered(): void
    {
        $source = $this->source('11889');
        $source['attributes'] = [
            'z_custom' => ['Секрет Z'],
            'material' => ['полиэстер'],
            'a_custom' => ['Секрет A'],
            'color' => ['белый'],
        ];

        $result = (new TemplateProductRewriter)->rewrite($source);

        $this->assertContains('unknown_attribute:a_custom', $result->warnings);
        $this->assertContains('unknown_attribute:z_custom', $result->warnings);
        $this->assertSame($result->warnings, array_values(array_unique($result->warnings)));
        $sortedWarnings = $result->warnings;
        sort($sortedWarnings, SORT_STRING);
        $this->assertSame($sortedWarnings, $result->warnings);
        $publicText = $result->title.' '.$result->summary.' '.$result->description;
        $this->assertStringNotContainsString('Секрет A', $publicText);
        $this->assertStringNotContainsString('Секрет Z', $publicText);
    }

    public function test_hostile_markup_controls_contacts_exact_price_and_brand_are_removed(): void
    {
        $source = $this->source('11889');
        $source['title'] = '<b>Римская штора</b> KORTIN 160 мм +7 (906) 060‑99‑89 '
            .'идеальная премиальная скидка'."\u{202E}";
        $source['description'] = '&lt;script&gt;alert(1)&lt;/script&gt; Цена 2708.00. '
            .'Пишите shop@example.test, звоните +7 (906) 060‑99‑89. Самая лучшая акция, '
            .'идеальная премиальная модель: бесплатная доставка и скидка!';
        $source['attributes'] = [
            'material' => ['&lt;b&gt;полиэстер&lt;/b&gt;'."\u{200B}"],
            'width' => ['160 мм'],
            'manufacturer' => ['KORTIN'],
        ];

        $result = (new TemplateProductRewriter)->rewrite($source);
        $publicText = mb_strtolower($result->title.' '.$result->summary.' '.$result->description);

        $this->assertStringNotContainsString('<', $publicText);
        $this->assertStringNotContainsString('kortin', $publicText);
        $this->assertStringNotContainsString('2708', $publicText);
        $this->assertStringNotContainsString('example.test', $publicText);
        $this->assertStringNotContainsString('+7', $publicText);
        $this->assertStringNotContainsString('скид', $publicText);
        $this->assertStringNotContainsString('бесплат', $publicText);
        $this->assertStringNotContainsString('идеаль', $publicText);
        $this->assertStringNotContainsString('премиаль', $publicText);
        $this->assertStringNotContainsString("\u{202E}", $publicText);
        $this->assertStringNotContainsString("\u{200B}", $publicText);
        $this->assertStringContainsString('160 мм', $publicText);
        $this->assertContains('removed_branding', $result->warnings);
        $this->assertContains('removed_contact', $result->warnings);
        $this->assertContains('removed_price', $result->warnings);
        $this->assertContains('removed_promotional', $result->warnings);
    }

    public function test_sparse_facts_remain_short_and_receive_bounds_warnings_without_filler(): void
    {
        $result = (new TemplateProductRewriter)->rewrite([
            'external_id' => '11889',
            'title' => 'Римская штора 11889',
            'description' => '',
            'source_price' => '2708.00',
            'attributes' => [],
        ]);

        $this->assertSame('Римская штора 11889', $result->title);
        $this->assertLessThan(180, mb_strlen($result->description));
        $this->assertContains('description_out_of_bounds', $result->warnings);
        $this->assertContains('summary_out_of_bounds', $result->warnings);
        $this->assertContains('title_out_of_bounds', $result->warnings);
        $this->assertContains('missing_material', $result->warnings);
        $this->assertStringNotContainsString('идеаль', mb_strtolower($result->description));
        $this->assertSame(1, substr_count($result->description, 'Римская штора 11889'));
    }

    public function test_formatted_exact_price_and_double_encoded_markup_are_removed_without_stripping_dimensions(): void
    {
        $source = $this->source('11889');
        $source['title'] = '&amp;lt;script&amp;gt;alert(1)&amp;lt;/script&amp;gt; '
            ."Римская штора 12\u{2009}345 ₽ 160 мм";
        $source['description'] = "Цена 12\u{2009}345 ₽, ширина 160 мм.";
        $source['source_price'] = '12345.00';
        $source['attributes'] = ['width' => ['160 мм']];

        $result = (new TemplateProductRewriter)->rewrite($source);
        $publicText = mb_strtolower($result->title.' '.$result->summary.' '.$result->description);

        $this->assertStringNotContainsString('<', $publicText);
        $this->assertStringNotContainsString('script', $publicText);
        $this->assertStringNotContainsString('alert', $publicText);
        $this->assertStringNotContainsString('12345', preg_replace('/\s/u', '', $publicText) ?? $publicText);
        $this->assertStringNotContainsString('₽', $publicText);
        $this->assertStringContainsString('160 мм', $publicText);
        $this->assertContains('removed_markup', $result->warnings);
        $this->assertContains('removed_price', $result->warnings);
    }

    /**
     * @return array<string, mixed>
     */
    private function source(string $externalId): array
    {
        return [
            'external_id' => $externalId,
            'title' => 'Римская штора KORTIN VELVET белоснежный',
            'description' => '<p>Лучшая цена 2708 ₽. Купить в Rimskie.com с доставкой. '
                .'https://rimskie.com/products/'.$externalId.' Телефон +7 906 060-99-89.</p>',
            'source_price' => '2708.00',
            'attributes' => [
                'material' => ['полиэстер'],
                'color' => ['белый'],
            ],
        ];
    }
}
