<?php

namespace Tests\Unit;

use App\Services\CatalogImport\TemplateLandingRewriter;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class TemplateLandingRewriterTest extends TestCase
{
    public function test_landing_copy_uses_only_the_approved_label_and_slug_deterministically(): void
    {
        $rewriter = new TemplateLandingRewriter;

        $first = $rewriter->rewrite('Белые', 'rimskie-shtory-belye');
        $second = $rewriter->rewrite('Белые', 'rimskie-shtory-belye');

        $this->assertEquals($first, $second);
        $copy = mb_strtolower(implode(' ', [
            $first->title,
            $first->h1,
            $first->intro,
            $first->description,
            $first->seo,
        ]));
        $this->assertStringContainsString('белые', $copy);
        $this->assertStringContainsString('римск', $copy);
        $this->assertStringNotContainsString('rimskie.com', $copy);
        $this->assertStringNotContainsString('купить', $copy);
        $this->assertSame([], $first->warnings);
    }

    public function test_invalid_or_suspicious_landing_input_is_rejected_or_flagged(): void
    {
        $rewriter = new TemplateLandingRewriter;

        try {
            $rewriter->rewrite('Белые', 'Rimskie_Shtory');
            $this->fail('Invalid landing slug was silently fixed.');
        } catch (InvalidArgumentException $error) {
            $this->assertStringContainsString('slug', $error->getMessage());
        }

        $result = $rewriter->rewrite('A', 'a');
        $this->assertContains('awkward_label', $result->warnings);
    }

    public function test_hostile_label_tokens_are_removed_from_every_public_field(): void
    {
        $result = (new TemplateLandingRewriter)->rewrite(
            $this->encodeRepeatedly('<b>Белые</b>', 6)
                .' KOR TIN R.I.M.S.K.I.E example.ru/catalog +7.999.123.45.67 8/999/123/45/67 '
                .'12 345 ₽ экологичные гипоаллергенные №1 номер один'."\u{202E}",
            'rimskie-shtory-belye',
        );
        $copy = mb_strtolower(implode(' ', [
            $result->title,
            $result->h1,
            $result->intro,
            $result->description,
            $result->seo,
        ]));

        $this->assertStringContainsString('белые', $copy);
        $this->assertStringNotContainsString('<', $copy);
        $this->assertStringNotContainsString('&lt;', $copy);
        foreach (['kor tin', 'r.i.m.s.k.i.e', 'example.ru', '+7.999', '8/999', '12 345', '₽', 'экологич', 'гипоаллерген', '№1', 'номер один'] as $blocked) {
            $this->assertStringNotContainsString($blocked, $copy);
        }
        $this->assertStringNotContainsString("\u{202E}", $copy);
        $this->assertContains('removed_branding', $result->warnings);
        $this->assertContains('removed_contact', $result->warnings);
        $this->assertContains('removed_control_characters', $result->warnings);
        $this->assertContains('removed_markup', $result->warnings);
        $this->assertContains('removed_price', $result->warnings);
        $this->assertContains('removed_promotional', $result->warnings);
        $sorted = $result->warnings;
        sort($sorted, SORT_STRING);
        $this->assertSame($sorted, $result->warnings);
    }

    public function test_real_label_shapes_read_naturally_without_doubling_category_name(): void
    {
        $rewriter = new TemplateLandingRewriter;

        foreach ([
            ['150 см', '150-sm', 'Римские шторы 150 см'],
            ['Во французском стиле', 'shtory-vo-francuzskom-stile', 'Римские шторы во французском стиле'],
            ['С Алисой', 's-alisoy', 'Римские шторы с Алисой'],
            ['На арочное окно', 'rimskie-shtory-na-arochnoe-okno', 'Римские шторы на арочное окно'],
            ['Из рогожки', 'rimskie-shtory-iz-rogozhi', 'Римские шторы из рогожки'],
            ['С кантом', 's-kantom', 'Римские шторы с кантом'],
            ['Крепление без сверления', 'rimskie-shtory-bez-sverleniya', 'Римские шторы с креплением без сверления'],
            ['Прованс', 'provans', 'Римские шторы в стиле прованс'],
        ] as [$label, $slug, $expectedH1]) {
            $result = $rewriter->rewrite($label, $slug);
            $this->assertSame($expectedH1, $result->h1);
            $this->assertSame(1, substr_count(mb_strtolower($result->h1), 'римские шторы'));
        }

        $alreadyNamed = $rewriter->rewrite(
            'Римские шторы для офиса',
            'rimskie-shtory-na-stvorku',
        );
        $this->assertSame(1, substr_count(mb_strtolower($alreadyNamed->h1), 'римские шторы'));
    }

    public function test_all_literal_source_labels_produce_unique_natural_h1_copy(): void
    {
        $sources = json_decode(
            file_get_contents(dirname(__DIR__, 2).'/config/rimskie-import-sources.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        $this->assertCount(46, $sources);

        $h1s = [];
        foreach ($sources as $source) {
            $result = (new TemplateLandingRewriter)->rewrite($source['label'], $source['target_slug']);
            $normalizedH1 = mb_strtolower($result->h1);
            $this->assertSame(1, substr_count($normalizedH1, 'римские шторы'), $source['label']);
            $this->assertStringNotContainsString(':', $result->h1, $source['label']);
            $this->assertDoesNotMatchRegularExpression(
                '/^(?:на|в|во|из|с|без|для)\b.*римские шторы/ui',
                $result->h1,
                $source['label'],
            );
            $this->assertSame([], $result->warnings, $source['label']);
            $h1s[] = $normalizedH1;
        }
        $this->assertCount(46, array_unique($h1s));
        $this->assertContains('римские шторы с креплением без сверления', $h1s);
        $this->assertContains('римские шторы в стиле прованс', $h1s);
    }

    private function encodeRepeatedly(string $value, int $passes): string
    {
        for ($pass = 0; $pass < $passes; $pass++) {
            $value = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        return $value;
    }
}
