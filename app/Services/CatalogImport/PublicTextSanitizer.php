<?php

namespace App\Services\CatalogImport;

use App\Data\CatalogImport\SanitizedPublicText;

final class PublicTextSanitizer
{
    private const MAX_ENTITY_DECODE_PASSES = 12;

    public function sanitize(string $value, ?string $sourcePrice = null): SanitizedPublicText
    {
        $warnings = [];
        $decoded = $value;
        for ($pass = 0; $pass < self::MAX_ENTITY_DECODE_PASSES; $pass++) {
            $next = html_entity_decode($decoded, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if ($next === $decoded) {
                break;
            }
            $warnings[] = 'decoded_entities';
            $decoded = $next;
        }

        $withoutExecutableMarkup = preg_replace(
            '/<(?:script|style)\b[^>]*>.*?<\/(?:script|style)>/uis',
            ' ',
            $decoded,
        ) ?? $decoded;
        $withoutTags = strip_tags($withoutExecutableMarkup);
        if ($withoutTags !== $decoded) {
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

        $separator = '[\s\p{P}\p{S}_]*';
        $patterns = [
            'removed_contact' => [
                '/(?:https?|ftp):\/\/\S+|www\.\S+/ui',
                '/[\p{L}\p{N}.+_-]+@[\p{L}\p{N}.-]+\.[\p{L}]{2,}/ui',
                '/(?<![\p{L}\p{N}@])(?:[\p{L}\p{N}-]+\.)+(?:ru|рф|com|net|org|site|online)\b(?:\/\S*)?/ui',
                '/(?<!\d)(?:\+?7|8)[\s().\/\p{Pd}-]*\d{3}[\s().\/\p{Pd}-]*\d{3}'
                    .'[\s.\/\p{Pd}-]*\d{2}[\s.\/\p{Pd}-]*\d{2}(?!\d)/u',
            ],
            'removed_branding' => [
                '/(?<!\p{L})(?:k'.$separator.'o'.$separator.'r'.$separator.'t'.$separator.'i'.$separator.'n|'
                    .'r'.$separator.'i'.$separator.'m'.$separator.'s'.$separator.'k'.$separator.'i'.$separator.'e'
                    .'(?:'.$separator.'c'.$separator.'o'.$separator.'m)?)(?!\p{L})/ui',
            ],
            'removed_promotional' => [
                '/\b(?:купить|заказать|доставк\p{L}*|лучш\p{L}*|гаранти\p{L}*|акци\p{L}*|'
                    .'скидк\p{L}*|бесплатн\p{L}*|идеальн\p{L}*|премиальн\p{L}*|'
                    .'экологич\p{L}*|гипоаллерген\p{L}*)\b|№\s*1\b|\bномер\s+один\b/ui',
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

        if (preg_match('/&(?:#\d+|#x[0-9a-f]+|[a-z][a-z0-9_-]*);/ui', $cleaned) === 1) {
            $warnings[] = 'removed_unresolved_entity';
            $cleaned = '';
        } else {
            $cleaned = preg_replace('/\s+/u', ' ', $cleaned) ?? $cleaned;
            $cleaned = preg_replace('/\s+([,.;:!?])/u', '$1', $cleaned) ?? $cleaned;
            $cleaned = trim($cleaned, " \t\n\r\0\x0B,.;:-");
        }

        $warnings = array_values(array_unique($warnings));
        sort($warnings, SORT_STRING);

        return new SanitizedPublicText($cleaned, $warnings);
    }
}
