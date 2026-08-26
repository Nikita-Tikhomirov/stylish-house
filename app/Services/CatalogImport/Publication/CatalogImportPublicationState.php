<?php

namespace App\Services\CatalogImport\Publication;

use Illuminate\Support\Facades\DB;
use JsonException;

final class CatalogImportPublicationState
{
    /** @return array<string, mixed> */
    public function row(string $table, int $id): array
    {
        $row = DB::table($table)->where('id', $id)->first();
        if ($row === null) {
            throw new CatalogImportPublicationException("Published $table row $id is missing.");
        }

        return (array) $row;
    }

    /** @param array<string, mixed> $row */
    public function fingerprint(array $row): string
    {
        try {
            return hash('sha256', json_encode(
                $this->canonicalize($row),
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR,
            ));
        } catch (JsonException) {
            throw new CatalogImportPublicationException('Published catalog state cannot be fingerprinted.');
        }
    }

    /** @return array<int, array<string, mixed>> */
    public function pivots(string $table, string $foreignKey, int $id): array
    {
        return DB::table($table)
            ->where($foreignKey, $id)
            ->orderBy($foreignKey)
            ->orderBy($table === 'catalog_collection_product' ? 'subcategory_id' : 'attribute_value_id')
            ->get()
            ->map(static fn (object $row): array => (array) $row)
            ->all();
    }

    public function assertSnapshotRow(string $table, array $snapshot): void
    {
        if (($snapshot['version'] ?? null) !== 1
            || ! is_int($snapshot['entity_id'] ?? null)
            || ! is_array($snapshot['row'] ?? null)
            || ! is_string($snapshot['fingerprint'] ?? null)) {
            throw new CatalogImportPublicationException('Publication snapshot is missing or unsupported.');
        }
        $current = $this->row($table, $snapshot['entity_id']);
        if (! hash_equals($snapshot['fingerprint'], $this->fingerprint($snapshot['row']))
            || ! hash_equals($snapshot['fingerprint'], $this->fingerprint($current))) {
            throw new CatalogImportPublicationException(
                'Published catalog row changed after publication; refusing unsafe operation.'
            );
        }
    }

    public function equivalent(array $left, array $right): bool
    {
        return hash_equals($this->fingerprint($left), $this->fingerprint($right));
    }

    /**
     * @param  array<int, int>  $valueIds
     * @return array{attributes: array<int, array<string, mixed>>, values: array<int, array<string, mixed>>}
     */
    public function attributeMetadata(array $valueIds): array
    {
        $valueIds = array_values(array_unique(array_map('intval', $valueIds)));
        sort($valueIds, SORT_NUMERIC);
        $values = DB::table('catalog_attribute_values')
            ->whereIn('id', $valueIds)
            ->orderBy('id')
            ->get()
            ->map(static fn (object $row): array => (array) $row)
            ->all();
        if (count($values) !== count($valueIds)) {
            throw new CatalogImportPublicationException('Published attribute value metadata is incomplete.');
        }
        $attributeIds = array_values(array_unique(array_map(
            static fn (array $row): int => (int) $row['catalog_attribute_id'],
            $values,
        )));
        sort($attributeIds, SORT_NUMERIC);
        $attributes = DB::table('catalog_attributes')
            ->whereIn('id', $attributeIds)
            ->orderBy('id')
            ->get()
            ->map(static fn (object $row): array => (array) $row)
            ->all();
        if (count($attributes) !== count($attributeIds)) {
            throw new CatalogImportPublicationException('Published attribute metadata is incomplete.');
        }

        return ['attributes' => $attributes, 'values' => $values];
    }

    private function canonicalize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }
        if (array_is_list($value)) {
            return array_map(fn (mixed $entry): mixed => $this->canonicalize($entry), $value);
        }
        ksort($value, SORT_STRING);

        return array_map(fn (mixed $entry): mixed => $this->canonicalize($entry), $value);
    }
}
