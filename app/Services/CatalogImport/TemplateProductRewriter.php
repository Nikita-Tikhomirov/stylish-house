<?php

namespace App\Services\CatalogImport;

use App\Data\CatalogImport\RewrittenProductContent;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class TemplateProductRewriter implements ProductContentRewriter
{
    public function __construct(
        private readonly PublicTextSanitizer $sanitizer = new PublicTextSanitizer,
    ) {}

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
        $titleResult = $this->sanitizer->sanitize($sourceTitle, $sourcePrice);
        $descriptionResult = $this->sanitizer->sanitize(
            $sourceDescription,
            $sourcePrice,
        );
        $title = $titleResult->value;
        $sanitizedSourceDescription = $descriptionResult->value;
        $warnings = array_merge($warnings, $titleResult->warnings, $descriptionResult->warnings);
        if ($title === '') {
            $title = 'Товар '.$externalId;
            $warnings[] = 'empty_sanitized_title';
        }
        $title = $this->truncateField($title, 140, 'title', $warnings);

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
        $summary = $this->truncateField($summary, 220, 'summary', $warnings);
        $description = $this->truncateField($description, 1000, 'description', $warnings);

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
                $result = $this->sanitizer->sanitize($value, $sourcePrice);
                $warnings = array_merge($warnings, $result->warnings);
                if ($result->value !== '') {
                    $sanitizedValues[] = $result->value;
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

    /** @param  array<int, string>  $warnings */
    private function truncateField(string $value, int $maxLength, string $field, array &$warnings): string
    {
        if (mb_strlen($value) <= $maxLength) {
            return $value;
        }

        $ellipsis = '…';
        $budget = $maxLength - mb_strlen($ellipsis);
        preg_match_all('/\X/u', $value, $matches);
        $prefix = '';
        foreach ($matches[0] ?? [] as $grapheme) {
            if (mb_strlen($prefix.$grapheme) > $budget) {
                break;
            }
            $prefix .= $grapheme;
        }
        $wordSafe = preg_replace('/\s+\S*$/u', '', $prefix) ?? $prefix;
        if (trim($wordSafe) !== '') {
            $prefix = $wordSafe;
        }
        $prefix = rtrim($prefix, " \t\n\r\0\x0B,.;:-—");
        $warnings[] = $field.'_out_of_bounds';
        $warnings[] = $field.'_truncated';

        return $prefix.$ellipsis;
    }

    private function normalizedSimilarityText(string $value): string
    {
        $normalized = mb_strtolower($value);
        $normalized = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $normalized) ?? $normalized;

        return trim(preg_replace('/\s+/u', ' ', $normalized) ?? $normalized);
    }
}
