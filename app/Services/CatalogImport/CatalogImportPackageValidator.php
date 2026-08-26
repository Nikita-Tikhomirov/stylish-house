<?php

namespace App\Services\CatalogImport;

use App\Data\CatalogImport\ValidatedCatalogImportPackage;
use InvalidArgumentException;
use JsonException;

final class CatalogImportPackageValidator
{
    public const MANIFEST_SCHEMA = 'stylish-house.catalog-import/v1';

    public const CONFIG_SCHEMA = 'stylish-house.catalog-import-run/v1';

    /** @var array<string, string> */
    private const ATTRIBUTE_ALIASES = [
        'tip' => 'type',
        'svetopronitsaemost' => 'opacity',
        'zatemnenie' => 'opacity',
        'faktura' => 'texture',
        'kreplenie' => 'mounting',
        'ustanovka' => 'mounting',
        'upravlenie' => 'control',
        'pomeshchenie' => 'room',
        'komnata' => 'room',
        'stil' => 'style',
        'shirina' => 'width',
        'vysota' => 'height',
        'sostav' => 'composition',
        'proizvoditel' => 'manufacturer',
        'plotnost' => 'density',
        'kant' => 'trim',
        'otdelka' => 'trim',
    ];

    public function validate(string $manifestPath): ValidatedCatalogImportPackage
    {
        $resolvedManifest = realpath($manifestPath);
        if ($resolvedManifest === false || ! is_file($resolvedManifest) || is_link($manifestPath)) {
            $this->fail('manifest path must reference a regular non-symlink file');
        }
        $packageDirectory = realpath(dirname($resolvedManifest));
        if ($packageDirectory === false || ! is_dir($packageDirectory)) {
            $this->fail('manifest package directory is unavailable');
        }

        $contents = file_get_contents($resolvedManifest);
        if ($contents === false) {
            $this->fail('manifest file cannot be read');
        }
        try {
            $manifest = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $this->fail('manifest must contain valid UTF-8 JSON');
        }
        if (! is_array($manifest) || array_is_list($manifest)) {
            $this->fail('manifest root must be an object');
        }
        if (($manifest['schema_version'] ?? null) !== self::MANIFEST_SCHEMA) {
            $this->fail('schema_version is unsupported');
        }

        $runId = $manifest['run_id'] ?? null;
        if (! is_string($runId) || ! $this->isSafeIdentifier($runId)) {
            $this->fail('run_id is not a safe identifier');
        }
        $config = $manifest['config'] ?? null;
        if (! is_array($config) || array_is_list($config)
            || ($config['schema_version'] ?? null) !== self::CONFIG_SCHEMA) {
            $this->fail('config.schema_version is unsupported');
        }
        $configDigest = $manifest['config_digest'] ?? null;
        if (! is_string($configDigest) || ! preg_match('/^[a-f0-9]{64}$/D', $configDigest)) {
            $this->fail('config_digest must be a lowercase SHA-256');
        }
        $calculatedConfigDigest = hash('sha256', $this->canonicalJson($config));
        if (! hash_equals($calculatedConfigDigest, $configDigest)) {
            $this->fail('config_digest does not match canonical config');
        }

        $state = $manifest['state'] ?? null;
        if (! is_array($state) || array_is_list($state) || ($state['status'] ?? null) !== 'completed') {
            $this->fail('state must be a completed object');
        }
        if (($state['configDigest'] ?? null) !== $configDigest) {
            $this->fail('state.configDigest must equal config_digest');
        }

        $configSources = $this->validateConfig($config);
        $stateSources = $this->validateState(
            $state,
            $configSources,
            $config['limits']['max_requests'],
        );
        $sources = $this->validatePersistedSources($manifest['sources'] ?? null, $configSources, $stateSources);
        $products = $this->validateProducts($manifest['products'] ?? null);
        $images = $this->validateImages(
            $manifest['images'] ?? null,
            $products,
            $packageDirectory,
        );
        $memberships = $this->validateMemberships(
            $manifest['memberships'] ?? null,
            $sources,
            $products,
        );
        $this->validateCompletedProductIds($state['completedProductIds'] ?? null, $products);

        $counts = [
            'sources' => count($sources),
            'products' => count($products),
            'memberships' => count($memberships),
            'images' => count($images),
        ];
        $suppliedCounts = $manifest['counts'] ?? null;
        if (! is_array($suppliedCounts) || array_is_list($suppliedCounts)) {
            $this->fail('counts must be an object');
        }
        foreach ($counts as $key => $count) {
            if (($suppliedCounts[$key] ?? null) !== $count) {
                $this->fail('counts.'.$key.' does not match recomputed value');
            }
        }

        return new ValidatedCatalogImportPackage(
            manifestPath: $resolvedManifest,
            packageDirectory: $packageDirectory,
            schemaVersion: self::MANIFEST_SCHEMA,
            configSchemaVersion: self::CONFIG_SCHEMA,
            runId: $runId,
            configDigest: $configDigest,
            manifestDigest: hash('sha256', $this->canonicalJson($manifest)),
            requestCount: $state['requestCount'],
            config: $config,
            sources: $sources,
            products: $products,
            images: $images,
            memberships: $memberships,
            counts: $counts,
            pageCount: array_sum(array_column($sources, 'pages')),
        );
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<int, array<string, mixed>>
     */
    private function validateConfig(array $config): array
    {
        $limits = $config['limits'] ?? null;
        if (! is_array($limits) || array_is_list($limits)
            || ! $this->isIntegerRange($limits['html_delay_ms'] ?? null)
            || ! $this->isIntegerRange($limits['image_delay_ms'] ?? null)
            || ! is_int($limits['hourly_requests'] ?? null) || $limits['hourly_requests'] <= 0
            || ! $this->isBackoff($limits['backoff_ms'] ?? null)
            || ($limits['concurrency'] ?? null) !== 1
            || ! array_key_exists('max_requests', $limits)
            || ! array_key_exists('max_products', $limits)
            || ! $this->isOptionalPositiveInt($limits['max_requests'] ?? null)
            || ! $this->isOptionalPositiveInt($limits['max_products'] ?? null)) {
            $this->fail('config.limits does not match the collector contract');
        }

        $sources = $config['sources'] ?? null;
        if (! is_array($sources) || ! array_is_list($sources) || $sources === []) {
            $this->fail('config.sources must be a non-empty list');
        }
        $slugs = [];
        $orders = [];
        $previousOrder = 0;
        foreach ($sources as $index => $source) {
            if (! is_array($source) || array_is_list($source)) {
                $this->fail("config.sources.$index must be an object");
            }
            $slug = $source['sourceSlug'] ?? null;
            $url = $source['sourceUrl'] ?? null;
            $label = $source['label'] ?? null;
            $sortOrder = $source['sortOrder'] ?? null;
            if (! is_string($slug) || ! preg_match('/^[a-z0-9][a-z0-9-]{0,127}$/D', $slug)) {
                $this->fail("config.sources.$index sourceSlug is invalid");
            }
            if (isset($slugs[$slug])) {
                $this->fail('config contains duplicate source slug '.$slug);
            }
            if (! is_string($label) || trim($label) === '' || $label !== trim($label)) {
                $this->fail("config.sources.$index label is invalid");
            }
            $this->validateDonorUrl($url, 'category', "config.sources.$index sourceUrl");
            if (($source['enabled'] ?? null) !== true || ! is_int($sortOrder) || $sortOrder <= 0
                || isset($orders[$sortOrder]) || $sortOrder <= $previousOrder
                || ($source['pendingProducts'] ?? null) !== []
                || ($source['completed'] ?? null) !== false
                || ($source['pages'] ?? null) !== 0
                || ($source['nextPageUrl'] ?? null) !== $url) {
                $this->fail("config.sources.$index initialization fields are contradictory");
            }
            $slugs[$slug] = true;
            $orders[$sortOrder] = true;
            $previousOrder = $sortOrder;
        }

        return $sources;
    }

    /**
     * @param  array<string, mixed>  $state
     * @param  array<int, array<string, mixed>>  $configSources
     * @return array<int, array<string, mixed>>
     */
    private function validateState(
        array $state,
        array $configSources,
        ?int $maxRequests,
    ): array {
        $requestCount = $state['requestCount'] ?? null;
        if (! is_int($requestCount) || $requestCount < 0
            || ($maxRequests !== null && $requestCount > $maxRequests)) {
            $this->fail('state.requestCount must be a nonnegative integer within max_requests');
        }
        $stateSources = $state['sources'] ?? null;
        if (! is_array($stateSources) || ! array_is_list($stateSources)
            || count($stateSources) !== count($configSources)) {
            $this->fail('state.sources differs from configured source set');
        }
        foreach ($configSources as $index => $configured) {
            $source = $stateSources[$index] ?? null;
            if (! is_array($source) || array_is_list($source)) {
                $this->fail("state.sources.$index must be an object");
            }
            foreach (['label', 'sourceSlug', 'sourceUrl', 'enabled', 'sortOrder'] as $field) {
                if (($source[$field] ?? null) !== ($configured[$field] ?? null)) {
                    $this->fail("state.sources.$index.$field differs from config");
                }
            }
            if (($source['completed'] ?? null) !== true
                || ! is_int($source['pages'] ?? null) || $source['pages'] < 1
                || ! array_key_exists('nextPageUrl', $source) || $source['nextPageUrl'] !== null
                || ($source['pendingProducts'] ?? null) !== []) {
                $this->fail("state.sources.$index progress is not complete");
            }
        }

        return $stateSources;
    }

    /**
     * @param  array<int, array<string, mixed>>  $configSources
     * @param  array<int, array<string, mixed>>  $stateSources
     * @return array<int, array<string, mixed>>
     */
    private function validatePersistedSources(
        mixed $records,
        array $configSources,
        array $stateSources,
    ): array {
        if (! is_array($records) || ! array_is_list($records)
            || count($records) !== count($configSources)) {
            $this->fail('sources differs from configured source set');
        }
        $normalized = [];
        foreach ($configSources as $index => $configured) {
            $record = $records[$index] ?? null;
            $state = $stateSources[$index];
            if (! is_array($record) || array_is_list($record)
                || ($record['label'] ?? null) !== $configured['label']
                || ($record['source_url'] ?? null) !== $configured['sourceUrl']
                || ($record['target_slug'] ?? null) !== $configured['sourceSlug']
                || ($record['enabled'] ?? null) !== true
                || ($record['sort_order'] ?? null) !== $configured['sortOrder']
                || ($record['status'] ?? null) !== 'completed'
                || ($record['pages'] ?? null) !== $state['pages']
                || ! array_key_exists('next_page_url', $record) || $record['next_page_url'] !== null) {
                $this->fail("sources.$index contradicts config or state");
            }
            $this->validateDonorUrl($record['source_url'], 'category', "sources.$index source_url");
            $normalized[] = [
                'label' => $record['label'],
                'source_url' => $record['source_url'],
                'target_slug' => $record['target_slug'],
                'enabled' => true,
                'sort_order' => $record['sort_order'],
                'status' => 'completed',
                'pages' => $record['pages'],
                'next_page_url' => null,
            ];
        }

        return $normalized;
    }

    /** @return array<int, array<string, mixed>> */
    private function validateProducts(mixed $records): array
    {
        if (! is_array($records) || ! array_is_list($records) || $records === []) {
            $this->fail('products must be a non-empty list');
        }
        $normalized = [];
        $seen = [];
        foreach ($records as $index => $record) {
            if (! is_array($record) || array_is_list($record)) {
                $this->fail("products.$index must be an object");
            }
            $externalId = $record['externalId'] ?? null;
            if (! is_string($externalId) || ! preg_match('/^\d{1,32}$/D', $externalId)) {
                $this->fail("products.$index externalId must be a digit string");
            }
            if (isset($seen[$externalId])) {
                $this->fail('products contains duplicate external ID '.$externalId);
            }
            $seen[$externalId] = true;
            foreach (['sourceUrl', 'sourceTitle', 'sourceDescription', 'sourcePrice', 'firstImageUrl', 'firstImagePath'] as $field) {
                if (! is_string($record[$field] ?? null) || trim($record[$field]) === '') {
                    $this->fail("products.$index.$field must be a non-empty string");
                }
            }
            $productId = $this->validateDonorUrl($record['sourceUrl'], 'product', "products.$index sourceUrl");
            if ($productId !== $externalId) {
                $this->fail("products.$index product URL ID differs from externalId");
            }
            $this->validateDonorUrl($record['firstImageUrl'], 'image', "products.$index firstImageUrl");
            if (! preg_match('/^\d+\.\d{2}$/D', $record['sourcePrice'])) {
                $this->fail("products.$index sourcePrice must be an exact decimal string");
            }
            $expectedImagePath = 'images/'.$externalId.'.webp';
            if ($record['firstImagePath'] !== $expectedImagePath) {
                $this->fail("products.$index image path must equal $expectedImagePath");
            }
            $normalized[] = [
                'external_id' => $externalId,
                'source_url' => $record['sourceUrl'],
                'source_title' => $record['sourceTitle'],
                'source_description' => $record['sourceDescription'],
                'source_price' => $record['sourcePrice'],
                'first_image_url' => $record['firstImageUrl'],
                'first_image_path' => $expectedImagePath,
                'attributes' => $this->normalizeAttributes($record['attributes'] ?? null, $index),
            ];
        }

        return $normalized;
    }

    /**
     * @param  array<int, array<string, mixed>>  $products
     * @return array<int, array<string, mixed>>
     */
    private function validateImages(mixed $records, array $products, string $packageDirectory): array
    {
        if (! is_array($records) || ! array_is_list($records)) {
            $this->fail('images must be a list');
        }
        $productsById = [];
        foreach ($products as $product) {
            $productsById[$product['external_id']] = $product;
        }
        $normalized = [];
        $seen = [];
        foreach ($records as $index => $record) {
            if (! is_array($record) || array_is_list($record)) {
                $this->fail("images.$index must be an object");
            }
            $externalId = $record['external_id'] ?? null;
            if (! is_string($externalId) || ! isset($productsById[$externalId]) || isset($seen[$externalId])) {
                $this->fail("images.$index external_id is duplicate or unknown");
            }
            $seen[$externalId] = true;
            $expectedPath = 'images/'.$externalId.'.webp';
            if (($record['path'] ?? null) !== $expectedPath
                || $productsById[$externalId]['first_image_path'] !== $expectedPath) {
                $this->fail("images.$index image path must equal $expectedPath");
            }
            $byteLength = $record['byte_length'] ?? null;
            $sha256 = $record['sha256'] ?? null;
            if (! is_int($byteLength) || $byteLength <= 0) {
                $this->fail("images.$index byte length is invalid");
            }
            if (! is_string($sha256) || ! preg_match('/^[a-f0-9]{64}$/D', $sha256)) {
                $this->fail("images.$index SHA-256 is invalid");
            }
            $imagePath = $this->resolveContainedImage($packageDirectory, $expectedPath, $index);
            if (filesize($imagePath) !== $byteLength) {
                $this->fail("images.$index byte length does not match file");
            }
            if (! hash_equals($sha256, hash_file('sha256', $imagePath))) {
                $this->fail("images.$index SHA-256 does not match file");
            }
            $bytes = file_get_contents($imagePath);
            if ($bytes === false || ! $this->isWebp($bytes)) {
                $this->fail("images.$index is not a structurally valid WebP");
            }
            $normalized[] = [
                'external_id' => $externalId,
                'path' => $expectedPath,
                'absolute_path' => $imagePath,
                'byte_length' => $byteLength,
                'sha256' => $sha256,
            ];
        }
        if (count($normalized) !== count($products)) {
            $this->fail('images must contain exactly one image per product');
        }

        return $normalized;
    }

    /**
     * @param  array<int, array<string, mixed>>  $sources
     * @param  array<int, array<string, mixed>>  $products
     * @return array<int, array<string, string>>
     */
    private function validateMemberships(mixed $records, array $sources, array $products): array
    {
        if (! is_array($records) || ! array_is_list($records) || $records === []) {
            $this->fail('memberships must be a non-empty list');
        }
        $sourceSlugs = array_fill_keys(array_column($sources, 'target_slug'), true);
        $productIds = array_fill_keys(array_column($products, 'external_id'), true);
        $membershipsByProduct = [];
        $seen = [];
        $normalized = [];
        foreach ($records as $index => $record) {
            if (! is_array($record) || array_is_list($record)) {
                $this->fail("memberships.$index must be an object");
            }
            $sourceSlug = $record['sourceSlug'] ?? null;
            $externalId = $record['externalId'] ?? null;
            if (! is_string($sourceSlug) || ! isset($sourceSlugs[$sourceSlug])
                || ! is_string($externalId) || ! isset($productIds[$externalId])) {
                $this->fail("memberships.$index references an unknown source or product");
            }
            $key = $sourceSlug."\0".$externalId;
            if (isset($seen[$key])) {
                $this->fail("memberships.$index duplicates an existing pair");
            }
            $seen[$key] = true;
            $membershipsByProduct[$externalId] = true;
            $normalized[] = ['source_slug' => $sourceSlug, 'external_id' => $externalId];
        }
        foreach (array_keys($productIds) as $externalId) {
            if (! isset($membershipsByProduct[$externalId])) {
                $this->fail('product '.$externalId.' has no source membership');
            }
        }

        return $normalized;
    }

    /** @param  array<int, array<string, mixed>>  $products */
    private function validateCompletedProductIds(mixed $ids, array $products): void
    {
        if (! is_array($ids) || ! array_is_list($ids)
            || count(array_unique($ids, SORT_STRING)) !== count($ids)
            || array_filter($ids, static fn (mixed $id): bool => ! is_string($id) || ! preg_match('/^\d{1,32}$/D', $id))) {
            $this->fail('state.completedProductIds must contain unique digit strings');
        }
        $productIds = array_column($products, 'external_id');
        usort($ids, [$this, 'compareExternalIds']);
        usort($productIds, [$this, 'compareExternalIds']);
        if ($ids !== $productIds) {
            $this->fail('state.completedProductIds differs from products');
        }
    }

    /** @return array<string, array<int, string>> */
    private function normalizeAttributes(mixed $attributes, int $productIndex): array
    {
        if (! is_array($attributes) || ($attributes !== [] && array_is_list($attributes))) {
            $this->fail("products.$productIndex.attributes must be an object");
        }
        ksort($attributes, SORT_STRING);
        $normalized = [];
        foreach ($attributes as $sourceCode => $values) {
            if (! is_string($sourceCode)
                || ! preg_match('/^[a-z0-9]+(?:_[a-z0-9]+)*$/D', $sourceCode)
                || ! is_array($values) || ! array_is_list($values) || $values === []) {
                $this->fail("products.$productIndex attributes contain an invalid key or value list");
            }
            $targetCode = self::ATTRIBUTE_ALIASES[$sourceCode] ?? $sourceCode;
            foreach ($values as $value) {
                if (! is_string($value) || trim($value) === '' || $value !== trim($value)) {
                    $this->fail("products.$productIndex attributes contain an invalid value");
                }
                $normalized[$targetCode][] = $value;
            }
        }
        ksort($normalized, SORT_STRING);
        foreach ($normalized as &$values) {
            $values = array_values(array_unique($values, SORT_STRING));
            sort($values, SORT_STRING);
        }
        unset($values);

        return $normalized;
    }

    private function validateDonorUrl(mixed $value, string $kind, string $label): ?string
    {
        if (! is_string($value) || $value === '' || str_contains($value, '\\')
            || preg_match('/%(?:2e|2f|5c)|%25(?:2e|2f|5c)/i', $value)
            || preg_match('/[\x00-\x20\x7f]/', $value)) {
            $this->fail($label.' is not an approved rimskie.com URL');
        }
        if (preg_match('~(?:^|/)\.{1,2}(?:/|$|\?)~', $value)) {
            $this->fail($label.' contains an ambiguous dot segment');
        }
        $parts = parse_url($value);
        if (! is_array($parts) || ($parts['scheme'] ?? null) !== 'https'
            || ($parts['host'] ?? null) !== 'rimskie.com'
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['port'])
            || isset($parts['fragment'])) {
            $this->fail($label.' is not an approved rimskie.com URL');
        }
        $path = $parts['path'] ?? '';
        $productId = null;
        $allowed = match ($kind) {
            'category' => preg_match('/^\/catalog\/rimskie-shtory\/[a-z0-9-]+\/?$/iD', $path) === 1,
            'product' => preg_match('/^\/products\/(\d+)(?:-[a-z0-9-]+)?\/?$/iD', $path, $matches) === 1,
            'image' => preg_match('/^\/(?:images|media|storage|upload|uploads)\/.+/D', $path) === 1,
            default => false,
        };
        if (! $allowed) {
            $this->fail($label.' is outside the approved rimskie.com '.$kind.' path');
        }
        $canonical = 'https://rimskie.com'.$path;
        if (array_key_exists('query', $parts)) {
            $canonical .= '?'.$parts['query'];
        }
        if ($canonical !== $value) {
            $this->fail($label.' is not an exact approved rimskie.com URL');
        }
        if ($kind === 'product') {
            $productId = $matches[1];
        }

        return $productId;
    }

    private function resolveContainedImage(string $packageDirectory, string $relativePath, int $index): string
    {
        $candidate = $packageDirectory.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        $resolved = realpath($candidate);
        if ($resolved === false || ! is_file($resolved) || is_link($candidate)) {
            $this->fail("images.$index must reference a regular image file");
        }
        $root = rtrim($packageDirectory, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
        $comparisonRoot = DIRECTORY_SEPARATOR === '\\' ? strtolower($root) : $root;
        $comparisonPath = DIRECTORY_SEPARATOR === '\\' ? strtolower($resolved) : $resolved;
        if (! str_starts_with($comparisonPath, $comparisonRoot)) {
            $this->fail("images.$index escapes the package directory");
        }
        $current = $packageDirectory;
        foreach (explode('/', $relativePath) as $part) {
            $current .= DIRECTORY_SEPARATOR.$part;
            if (is_link($current)) {
                $this->fail("images.$index traverses a symbolic link");
            }
        }
        $stats = lstat($resolved);
        if ($stats === false || (($stats['mode'] ?? 0) & 0170000) !== 0100000
            || ($stats['nlink'] ?? 1) !== 1) {
            $this->fail("images.$index must reference a regular image file");
        }

        return $resolved;
    }

    private function isWebp(string $bytes): bool
    {
        if (strlen($bytes) < 20 || substr($bytes, 0, 4) !== 'RIFF'
            || substr($bytes, 8, 4) !== 'WEBP'
            || ! in_array(substr($bytes, 12, 4), ['VP8 ', 'VP8L', 'VP8X'], true)) {
            return false;
        }
        $riffSize = unpack('Vsize', substr($bytes, 4, 4));
        $chunkSize = unpack('Vsize', substr($bytes, 16, 4));
        if (! is_array($riffSize) || ! is_array($chunkSize)) {
            return false;
        }
        $paddedChunkSize = $chunkSize['size'] + ($chunkSize['size'] % 2);

        return strlen($bytes) === $riffSize['size'] + 8
            && 20 + $paddedChunkSize <= strlen($bytes);
    }

    private function canonicalJson(mixed $value): string
    {
        try {
            return json_encode(
                $this->canonicalize($value),
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
            );
        } catch (JsonException) {
            $this->fail('manifest contains data that cannot be canonicalized');
        }
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

    private function isIntegerRange(mixed $value): bool
    {
        return is_array($value) && array_is_list($value) && count($value) === 2
            && is_int($value[0]) && is_int($value[1])
            && $value[0] >= 0 && $value[0] <= $value[1];
    }

    private function isBackoff(mixed $value): bool
    {
        return is_array($value) && array_is_list($value) && count($value) === 3
            && count(array_filter($value, static fn (mixed $entry): bool => ! is_int($entry) || $entry < 0)) === 0;
    }

    private function isOptionalPositiveInt(mixed $value): bool
    {
        return $value === null || (is_int($value) && $value > 0);
    }

    private function isSafeIdentifier(string $value): bool
    {
        return preg_match('/^[a-z0-9][a-z0-9._-]{0,127}$/iD', $value) === 1
            && $value !== '.' && $value !== '..' && ! str_contains($value, '..')
            && ! str_contains($value, ':') && ! preg_match('/[. ]$/D', $value)
            && ! preg_match('/^(?:con|prn|aux|nul|com[1-9]|lpt[1-9])(?:\..*)?$/iD', $value);
    }

    private function compareExternalIds(string $left, string $right): int
    {
        return strlen($left) <=> strlen($right) ?: strcmp($left, $right);
    }

    private function fail(string $message): never
    {
        throw new InvalidArgumentException('Catalog import manifest invariant failed: '.$message.'.');
    }
}
