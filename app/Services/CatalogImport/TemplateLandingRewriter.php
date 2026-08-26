<?php

namespace App\Services\CatalogImport;

use App\Data\CatalogImport\RewrittenLandingContent;
use InvalidArgumentException;

final class TemplateLandingRewriter implements LandingContentRewriter
{
    public function rewrite(string $label, string $targetSlug): RewrittenLandingContent
    {
        if (! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $targetSlug)
            || strlen($targetSlug) > 128) {
            throw new InvalidArgumentException('Landing target slug must be lowercase kebab-case.');
        }

        $warnings = [];
        $decoded = $label;
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
        $cleanLabel = strip_tags($withoutExecutableMarkup);
        if ($cleanLabel !== $label) {
            $warnings[] = 'removed_markup';
        }
        $withoutControls = preg_replace(
            '/[\x{0000}-\x{001F}\x{007F}\x{200B}-\x{200F}\x{202A}-\x{202E}\x{2060}-\x{206F}\x{FEFF}]/u',
            ' ',
            $cleanLabel,
        ) ?? $cleanLabel;
        if ($withoutControls !== $cleanLabel) {
            $warnings[] = 'removed_control_characters';
        }
        $cleanLabel = $withoutControls;
        $contactPatterns = [
            '/https?:\/\/\S+|www\.\S+/ui',
            '/[\p{L}\p{N}.+_-]+@[\p{L}\p{N}.-]+\.[\p{L}]{2,}/ui',
            '/(?:\+?7|8)[\s()\p{Pd}-]*\d{3}[\s()\p{Pd}-]*\d{3}[\s\p{Pd}-]*\d{2}[\s\p{Pd}-]*\d{2}/u',
        ];
        foreach ($contactPatterns as $pattern) {
            $next = preg_replace($pattern, ' ', $cleanLabel);
            if ($next !== null && $next !== $cleanLabel) {
                $warnings[] = 'removed_contact';
                $cleanLabel = $next;
            }
        }
        $withoutBrand = preg_replace('/\b(?:rimskie(?:\.com)?|kortin)\b/ui', ' ', $cleanLabel);
        if ($withoutBrand !== null && $withoutBrand !== $cleanLabel) {
            $warnings[] = 'removed_branding';
            $cleanLabel = $withoutBrand;
        }
        $cleanLabel = preg_replace('/\s+/u', ' ', $cleanLabel) ?? $cleanLabel;
        $cleanLabel = trim($cleanLabel, " \t\n\r\0\x0B,.;:-&");
        if (mb_strlen($cleanLabel) < 2 || mb_strlen($cleanLabel) > 80
            || preg_match('/https?:\/\/|www\.|@|(?:\+?7|8)[\s()\p{Pd}-]*\d{3}/ui', $cleanLabel)) {
            $warnings[] = 'awkward_label';
        }
        if ($cleanLabel === '') {
            throw new InvalidArgumentException('Landing label becomes empty after sanitization.');
        }
        if (preg_match('/\bштор\p{L}*/ui', $cleanLabel)) {
            $collectionName = $cleanLabel;
        } elseif (preg_match('/^(?:\d|на\b|в\b|во\b|для\b|из\b|с\b|со\b|без\b|под\b|к\b)/ui', $cleanLabel)) {
            $collectionName = 'Римские шторы '.$this->lowercaseFirst($cleanLabel);
        } elseif (preg_match('/(?:ый|ий|ая|яя|ое|ее|ые|ие|ой|ей|ых|их)$/ui', $cleanLabel)) {
            $collectionName = $cleanLabel.' римские шторы';
        } else {
            $collectionName = 'Римские шторы: '.$cleanLabel;
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
