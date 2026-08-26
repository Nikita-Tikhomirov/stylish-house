<?php

namespace App\Services\CatalogImport;

use App\Data\CatalogImport\RewrittenLandingContent;
use InvalidArgumentException;

final class TemplateLandingRewriter implements LandingContentRewriter
{
    public function __construct(
        private readonly PublicTextSanitizer $sanitizer = new PublicTextSanitizer,
    ) {}

    public function rewrite(string $label, string $targetSlug): RewrittenLandingContent
    {
        if (! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $targetSlug)
            || strlen($targetSlug) > 128) {
            throw new InvalidArgumentException('Landing target slug must be lowercase kebab-case.');
        }

        $sanitized = $this->sanitizer->sanitize($label);
        $warnings = $sanitized->warnings;
        $cleanLabel = $sanitized->value;
        if (mb_strlen($cleanLabel) < 2 || mb_strlen($cleanLabel) > 80
            || $sanitized->wasModified()) {
            $warnings[] = 'awkward_label';
        }
        if ($cleanLabel === '') {
            throw new InvalidArgumentException('Landing label becomes empty after sanitization.');
        }
        $normalizedLabel = mb_strtolower($cleanLabel);
        if ($normalizedLabel === 'крепление без сверления') {
            $collectionName = 'Римские шторы с креплением без сверления';
        } elseif ($normalizedLabel === 'прованс') {
            $collectionName = 'Римские шторы в стиле прованс';
        } elseif (preg_match('/\bштор\p{L}*/ui', $cleanLabel)) {
            $collectionName = $cleanLabel;
        } elseif (preg_match('/^(?:\d|на\b|в\b|во\b|для\b|из\b|с\b|со\b|без\b|под\b|к\b)/ui', $cleanLabel)) {
            $collectionName = 'Римские шторы '.$this->lowercaseFirst($cleanLabel);
        } elseif (preg_match('/(?:ый|ий|ая|яя|ое|ее|ые|ие|ой|ей|ых|их)$/ui', $cleanLabel)) {
            $collectionName = $cleanLabel.' римские шторы';
        } else {
            $collectionName = 'Римские шторы — '.$cleanLabel;
        }
        $warnings = array_values(array_unique($warnings));
        sort($warnings, SORT_STRING);

        return new RewrittenLandingContent(
            title: $collectionName.' — каталог',
            h1: $collectionName,
            intro: sprintf('В разделе собраны модели категории «%s».', $collectionName),
            description: sprintf(
                'Подборка объединяет модели, относящиеся к категории «%s». Параметры каждой модели перечислены в её карточке.',
                $collectionName,
            ),
            seo: sprintf(
                'Раздел «%s» позволяет сравнить модели по характеристикам, указанным в карточках товаров.',
                $collectionName,
            ),
            warnings: $warnings,
        );
    }

    private function lowercaseFirst(string $value): string
    {
        return mb_strtolower(mb_substr($value, 0, 1)).mb_substr($value, 1);
    }
}
