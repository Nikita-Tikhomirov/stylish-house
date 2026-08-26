<?php

namespace App\Services\CatalogImport;

use App\Data\CatalogImport\RewrittenProductContent;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class TemplateProductRewriter implements ProductContentRewriter
{
    /** @var array<string, string> */
    public const ATTRIBUTE_LABELS = [
        'type' => 'тип',
        'material' => 'материал',
        'color' => 'цвет',
        'opacity' => 'светопроницаемость',
        'texture' => 'фактура',
        'mounting' => 'крепление',
        'control' => 'управление',
        'room' => 'помещение',
        'style' => 'стиль',
        'width' => 'ширина',
        'height' => 'высота',
        'composition' => 'состав',
        'manufacturer' => 'производитель',
        'density' => 'плотность',
        'trim' => 'отделка',
    ];

    /**
     * @param  array<string, mixed>  $source
     */
    public function rewrite(array $source): RewrittenProductContent
    {
        $externalId = $source['external_id'] ?? null;
        $sourceTitle = $source['title'] ?? null;
        $sourceDescription = $source['description'] ?? '';
        $sourcePrice = $source['source_price'] ?? null;
        $attributes = $source['attributes'] ?? null;
        if (! is_string($externalId) || ! preg_match('/^\d{1,32}$/D', $externalId)
            || ! is_string($sourceTitle) || trim($sourceTitle) === ''
            || ! is_string($sourceDescription)
            || ($sourcePrice !== null && (! is_string($sourcePrice) || ! preg_match('/^\d+\.\d{2}$/D', $sourcePrice)))
            || ! is_array($attributes)) {
            throw new InvalidArgumentException('Product rewrite source is invalid.');
        }

        $warnings = [];
        [$title, $titleWarnings] = $this->sanitize($sourceTitle, $sourcePrice);
        [$sanitizedSourceDescription, $descriptionWarnings] = $this->sanitize(
            $sourceDescription,
            $sourcePrice,
        );
        $warnings = array_merge($warnings, $titleWarnings, $descriptionWarnings);
        if ($title === '') {
            $title = 'Товар '.$externalId;
            $warnings[] = 'empty_sanitized_title';
        }

        [$facts, $attributeWarnings] = $this->publicFacts($attributes, $sourcePrice);
        $warnings = array_merge($warnings, $attributeWarnings);
        if (! isset($attributes['material']) || $this->validValues($attributes['material']) === []) {
            $warnings[] = 'missing_material';
        }
        if ((! isset($attributes['type']) || $this->validValues($attributes['type']) === [])
            && ! preg_match('/\bштор\p{L}*/ui', $title)) {
            $warnings[] = 'missing_type';
        }

        $factsText = implode('; ', $facts);
        $summary = $facts === []
            ? sprintf('Модель «%s». Дополнительные характеристики не указаны.', $title)
            : sprintf(
                'Модель «%s» представлена со следующими характеристиками: %s. Остальные параметры стоит уточнить перед выбором.',
                $title,
                $factsText,
            );
        $description = $this->descriptionFor(
            hexdec(substr(hash('crc32b', $externalId), -1)) % 4,
            $title,
            $factsText,
        );

        $this->appendLengthWarnings($warnings, $title, $summary, $description);
        $sourceCopy = $this->normalizedSimilarityText($title.' '.$sanitizedSourceDescription);
        $publicCopy = $this->normalizedSimilarityText($title.' '.$summary.' '.$description);
        if ($sourceCopy !== '' && $publicCopy !== '') {
            similar_text($sourceCopy, $publicCopy, $similarity);
            if ($similarity >= 70.0) {
                $warnings[] = 'similar_text';
            }
        }

        $warnings = array_values(array_unique($warnings));
        sort($warnings, SORT_STRING);

        return new RewrittenProductContent(
            title: $title,
            summary: $summary,
            description: $description,
            slugBase: Str::slug($title) ?: 'product-'.$externalId,
            warnings: $warnings,
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array{0: array<int, string>, 1: array<int, string>}
     */
    private function publicFacts(array $attributes, ?string $sourcePrice): array
    {
        $facts = [];
        $warnings = [];
        foreach ($attributes as $code => $values) {
            if (! array_key_exists($code, self::ATTRIBUTE_LABELS)) {
                $warnings[] = 'unknown_attribute:'.$code;
            }
        }
        foreach (self::ATTRIBUTE_LABELS as $code => $label) {
            $values = $this->validValues($attributes[$code] ?? null);
            $sanitizedValues = [];
            foreach ($values as $value) {
                [$clean, $valueWarnings] = $this->sanitize($value, $sourcePrice);
                $warnings = array_merge($warnings, $valueWarnings);
                if ($clean !== '') {
                    $sanitizedValues[] = $clean;
                }
            }
            $sanitizedValues = array_values(array_unique($sanitizedValues));
            if ($sanitizedValues !== []) {
                $facts[] = $label.' — '.implode(', ', $sanitizedValues);
            }
        }

        return [$facts, $warnings];
    }

    /**
     * @return array<int, string>
     */
    private function validValues(mixed $values): array
    {
        if (! is_array($values)) {
            return [];
        }

        return array_values(array_filter(
            $values,
            static fn (mixed $value): bool => is_string($value) && trim($value) !== '',
        ));
    }

    /**
     * @return array{0: string, 1: array<int, string>}
     */
    private function sanitize(string $value, ?string $sourcePrice): array
    {
        $warnings = [];
        $decoded = $value;
        for ($pass = 0; $pass < 4; $pass++) {
            $next = html_entity_decode($decoded, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if ($next === $decoded) {
                break;
            }
            $decoded = $next;
        }
        $withoutExecutableMarkup = preg_replace(
            '/<(?:script|style)\b[^>]*>.*?<\/(?:script|style)>/uis',
            ' ',
            $decoded,
        ) ?? $decoded;
        $withoutTags = strip_tags($withoutExecutableMarkup);
        if ($withoutTags !== $value) {
            $warnings[] = 'removed_markup';
        }
        $cleaned = preg_replace(
            '/[\x{0000}-\x{001F}\x{007F}\x{200B}-\x{200F}\x{202A}-\x{202E}\x{2060}-\x{206F}\x{FEFF}]/u',
            ' ',
            $withoutTags,
        ) ?? $withoutTags;
        if ($cleaned !== $withoutTags) {
            $warnings[] = 'removed_control_characters';
        }

        $patterns = [
            'removed_contact' => [
                '/https?:\/\/\S+|www\.\S+/ui',
                '/[\p{L}\p{N}.+_-]+@[\p{L}\p{N}.-]+\.[\p{L}]{2,}/ui',
                '/(?:\+?7|8)[\s()\p{Pd}-]*\d{3}[\s()\p{Pd}-]*\d{3}[\s\p{Pd}-]*\d{2}[\s\p{Pd}-]*\d{2}/u',
            ],
            'removed_branding' => [
                '/\b(?:rimskie(?:\.com)?|kortin)\b/ui',
            ],
            'removed_promotional' => [
                '/\b(?:купить|заказать|доставк\p{L}*|лучш\p{L}*|гаранти\p{L}*|акци\p{L}*|'
                    .'скидк\p{L}*|бесплатн\p{L}*|идеальн\p{L}*|премиальн\p{L}*)\b/ui',
            ],
            'removed_price' => [
                '/(?<!\d)\d(?:[\d\x{00A0}\x{2009}\x{202F} ]*\d)?(?:[.,]\d{1,2})?'
                    .'[\s\x{00A0}\x{2009}\x{202F}]*(?:₽|руб(?:\.|лей|ля)?)(?!\p{L})/ui',
            ],
        ];
        if ($sourcePrice !== null) {
            $integerPrice = preg_replace('/\.00$/D', '', $sourcePrice) ?? $sourcePrice;
            $quotedDigits = array_map(
                static fn (string $digit): string => preg_quote($digit, '/'),
                str_split($integerPrice),
            );
            $priceDigits = implode('[\s\x{00A0}\x{2009}\x{202F}]*', $quotedDigits);
            $patterns['removed_price'][] = '/(?<![\d.,])'.$priceDigits
                .'(?:[.,]00)?(?!\d|[.,]\d|\s*(?:мм|см|м\b))/ui';
        }
        foreach ($patterns as $warning => $warningPatterns) {
            foreach ($warningPatterns as $pattern) {
                $next = preg_replace($pattern, ' ', $cleaned);
                if ($next !== null && $next !== $cleaned) {
                    $warnings[] = $warning;
                    $cleaned = $next;
                }
            }
        }
        $cleaned = preg_replace('/\s+/u', ' ', $cleaned) ?? $cleaned;
        $cleaned = preg_replace('/\s+([,.;:!?])/u', '$1', $cleaned) ?? $cleaned;

        return [
            trim($cleaned, " \t\n\r\0\x0B,.;:-"),
            array_values(array_unique($warnings)),
        ];
    }

    private function descriptionFor(int $layout, string $title, string $facts): string
    {
        if ($facts === '') {
            return sprintf(
                'Модель «%s». Дополнительные свойства модели не указаны; перед выбором их стоит уточнить.',
                $title,
            );
        }

        $intro = sprintf('Модель «%s» представлена в каталоге римских штор.', $title);
        $factSentence = sprintf('Среди указанных параметров: %s.', $facts);
        $scope = 'При сравнении вариантов можно ориентироваться на перечисленные свойства модели.';
        $check = 'Размер, крепление, управление и другие параметры стоит уточнить, если их нет среди характеристик.';

        $layouts = [
            [$intro, $factSentence, $scope, $check],
            [$factSentence, $intro, $check, $scope],
            [$check, $factSentence, $scope, $intro],
            [$scope, $intro, $factSentence, $check],
        ];

        return implode(' ', $layouts[$layout]);
    }

    /**
     * @param  array<int, string>  $warnings
     */
    private function appendLengthWarnings(
        array &$warnings,
        string $title,
        string $summary,
        string $description,
    ): void {
        if (mb_strlen($title) < 25 || mb_strlen($title) > 140) {
            $warnings[] = 'title_out_of_bounds';
        }
        if (mb_strlen($summary) < 80 || mb_strlen($summary) > 220) {
            $warnings[] = 'summary_out_of_bounds';
        }
        if (mb_strlen($description) < 180 || mb_strlen($description) > 1000) {
            $warnings[] = 'description_out_of_bounds';
        }
    }

    private function normalizedSimilarityText(string $value): string
    {
        $normalized = mb_strtolower($value);
        $normalized = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $normalized) ?? $normalized;

        return trim(preg_replace('/\s+/u', ' ', $normalized) ?? $normalized);
    }
}
