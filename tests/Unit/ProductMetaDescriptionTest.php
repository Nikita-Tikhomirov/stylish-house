<?php

namespace Tests\Unit;

use App\Support\ProductMetaDescription;
use PHPUnit\Framework\TestCase;

class ProductMetaDescriptionTest extends TestCase
{
    public function test_it_prefixes_the_clean_description_with_the_product_title_grammatically(): void
    {
        $description = ProductMetaDescription::make(
            'Стандарт Дарина абрикосовый',
            '<p>Практичные рулонные шторы   создают комфорт.</p>'
        );

        $this->assertSame(
            'Стандарт Дарина абрикосовый — практичные рулонные шторы создают комфорт.',
            $description
        );
    }

    public function test_it_does_not_repeat_a_title_already_present_in_the_description(): void
    {
        $description = ProductMetaDescription::make(
            'Рольворота RH77M антрацит',
            'Рольворота RH77M антрацит подходят для гаража и производственного помещения.'
        );

        $this->assertSame(1, substr_count($description, 'Рольворота RH77M антрацит'));
    }

    public function test_it_provides_a_useful_fallback_for_an_empty_description(): void
    {
        $description = ProductMetaDescription::make('Сантехнические роллеты белые', null);

        $this->assertStringStartsWith('Сантехнические роллеты белые —', $description);
        $this->assertStringContainsString('по индивидуальным размерам', $description);
    }

    public function test_it_limits_description_at_a_complete_word(): void
    {
        $description = ProductMetaDescription::make(
            'Рулонные шторы День-Ночь серые',
            str_repeat('Качественные материалы и аккуратный монтаж обеспечивают удобство эксплуатации. ', 5)
        );

        $this->assertLessThanOrEqual(160, mb_strlen($description));
        $this->assertDoesNotMatchRegularExpression('/\s[\pL\pN-]{1,20}$/u', $description);
        $this->assertStringEndsWith('…', $description);
    }
}
