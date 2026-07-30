<?php

namespace App\Support;

class ProductMetaDescription
{
    private const MAX_LENGTH = 160;

    public static function make(string $title, ?string $description): string
    {
        $cleanTitle = self::clean($title);
        $cleanDescription = self::clean($description ?? '');

        if ($cleanDescription === '') {
            $result = $cleanTitle.' — изготовление на заказ по индивидуальным размерам с замером, доставкой и установкой в Москве.';
        } elseif ($cleanTitle !== '' && mb_stripos($cleanDescription, $cleanTitle) === 0) {
            $result = $cleanDescription;
        } else {
            $result = $cleanTitle.' — '.self::lowercaseFirst($cleanDescription);
        }

        return self::limit($result);
    }

    private static function clean(string $value): string
    {
        $value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim((string) preg_replace('/\s+/u', ' ', $value));
    }

    private static function lowercaseFirst(string $value): string
    {
        if ($value === '') {
            return $value;
        }

        return mb_strtolower(mb_substr($value, 0, 1)).mb_substr($value, 1);
    }

    private static function limit(string $value): string
    {
        if (mb_strlen($value) <= self::MAX_LENGTH) {
            return $value;
        }

        $candidate = mb_substr($value, 0, self::MAX_LENGTH - 1);
        $candidate = (string) preg_replace('/\s+\S*$/u', '', $candidate);

        return rtrim($candidate, " \t\n\r\0\x0B,.;:!?").'…';
    }
}
