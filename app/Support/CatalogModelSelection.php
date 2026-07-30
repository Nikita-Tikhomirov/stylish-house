<?php

namespace App\Support;

final class CatalogModelSelection
{
    /**
     * Resolve a menu-provided model filter without escaping the catalog's configured scope.
     *
     * @param iterable<int|string> $availableModelIds
     * @return array<int>
     */
    public static function resolve(mixed $requested, iterable $availableModelIds, mixed $configured): array
    {
        $available = self::normalize($availableModelIds);
        $configuredIds = self::normalize(self::decodeConfigured($configured));
        $configuredIds = array_values(array_intersect($configuredIds, $available));
        $allowed = $configuredIds !== [] ? $configuredIds : $available;
        $requestedIds = self::normalize(is_array($requested) ? $requested : explode(',', (string) $requested));
        $requestedIds = array_values(array_intersect($requestedIds, $allowed));

        return $requestedIds !== [] ? $requestedIds : $configuredIds;
    }

    private static function decodeConfigured(mixed $configured): array
    {
        if (is_array($configured)) {
            return $configured;
        }

        if (! is_string($configured) || trim($configured) === '') {
            return [];
        }

        $decoded = json_decode($configured, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param iterable<mixed> $values
     * @return array<int>
     */
    private static function normalize(iterable $values): array
    {
        $ids = [];
        foreach ($values as $value) {
            if (filter_var($value, FILTER_VALIDATE_INT) === false || (int) $value <= 0) {
                continue;
            }

            $ids[] = (int) $value;
        }

        return array_values(array_unique($ids));
    }
}
