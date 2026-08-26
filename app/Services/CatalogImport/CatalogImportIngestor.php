<?php

namespace App\Services\CatalogImport;

use App\Data\CatalogImport\ValidatedCatalogImportPackage;
use App\Exceptions\CatalogImportOperationalException;
use App\Models\CatalogAttribute;
use App\Models\CatalogImportItem;
use App\Models\CatalogImportRun;
use App\Models\CatalogImportSource;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class CatalogImportIngestor
{
    private PublicTextSanitizer $publicTextSanitizer;

    private CatalogImportRewritePlanner $rewritePlanner;

    /** @var array<string, string> */
    private const ATTRIBUTE_TYPES = [
        'width' => CatalogAttribute::TYPE_NUMBER,
        'height' => CatalogAttribute::TYPE_NUMBER,
        'density' => CatalogAttribute::TYPE_NUMBER,
    ];

    public function __construct(
        ?ProductContentRewriter $productRewriter = null,
        ?LandingContentRewriter $landingRewriter = null,
        ?PublicTextSanitizer $publicTextSanitizer = null,
    ) {
        $this->publicTextSanitizer = $publicTextSanitizer ?? new PublicTextSanitizer;
        $this->rewritePlanner = new CatalogImportRewritePlanner(
            $productRewriter,
            $landingRewriter,
            $this->publicTextSanitizer,
        );
    }

    public function ingest(ValidatedCatalogImportPackage $package): CatalogImportRun
    {
        $disk = Storage::disk('local');
        $createdFiles = [];

        try {
            return DB::transaction(function () use (
                $package,
                $disk,
                &$createdFiles,
            ): CatalogImportRun {
                $existing = CatalogImportRun::query()
                    ->where('provider', 'rimskie.com')
                    ->where('external_run_id', $package->runId)
                    ->lockForUpdate()
                    ->first();
                if ($existing) {
                    $this->verifyExistingRun($existing, $package, $disk);

                    return $existing;
                }

                $rewritePlan = $this->rewritePlanner->plan($package);
                $landingDrafts = $rewritePlan->landings;
                $productDrafts = $rewritePlan->products;
                $attributeVisibility = $this->attributeVisibility($package);

                $run = CatalogImportRun::create([
                    'provider' => 'rimskie.com',
                    'external_run_id' => $package->runId,
                    'status' => CatalogImportRun::STATUS_STAGED,
                    'config' => $this->configEnvelope($package),
                    'source_count' => $package->counts['sources'],
                    'page_count' => $package->pageCount,
                    'unique_product_count' => $package->counts['products'],
                    'image_count' => $package->counts['images'],
                    'membership_count' => $package->counts['memberships'],
                    'duplicate_count' => 0,
                    'error_count' => 0,
                ]);

                $destinations = $this->copyImages($package, $disk, $createdFiles);
                $membershipsPerSource = array_count_values(array_column($package->memberships, 'source_slug'));
                $sourceModels = [];
                foreach ($package->sources as $source) {
                    $draft = $landingDrafts[$source['target_slug']];
                    $sourceModels[$source['target_slug']] = $run->sources()->create([
                        'label' => $source['label'],
                        'source_url' => $source['source_url'],
                        'target_slug' => $source['target_slug'],
                        'enabled' => $source['enabled'],
                        'status' => CatalogImportSource::STATUS_COMPLETED,
                        'sort_order' => $source['sort_order'],
                        'pages_count' => $source['pages'],
                        'items_count' => $membershipsPerSource[$source['target_slug']] ?? 0,
                        'next_page_url' => null,
                        'rewritten_title' => $draft->title,
                        'rewritten_h1' => $draft->h1,
                        'rewritten_intro' => $draft->intro,
                        'rewritten_description' => $draft->description,
                        'rewritten_seo' => $draft->seo,
                        'review_status' => CatalogImportSource::REVIEW_NEEDS_REVIEW,
                        'warnings' => $draft->warnings,
                    ]);
                }

                $imagesById = $this->keyBy($package->images, 'external_id');
                $itemModels = [];
                foreach ($package->products as $product) {
                    $externalId = $product['external_id'];
                    $image = $imagesById[$externalId];
                    $draft = $productDrafts[$externalId];
                    $item = $run->items()->create([
                        'provider' => 'rimskie.com',
                        'external_id' => $externalId,
                        'source_url' => $product['source_url'],
                        'source_title' => $product['source_title'],
                        'source_description' => $product['source_description'],
                        'source_price' => $product['source_price'],
                        'source_image_path' => $destinations[$externalId],
                        'source_image_sha256' => $image['sha256'],
                        'source_image_byte_length' => $image['byte_length'],
                        'rewritten_title' => $draft->title,
                        'rewritten_summary' => $draft->summary,
                        'rewritten_description' => $draft->description,
                        'rewritten_slug' => $this->itemSlug($draft->slugBase, $externalId),
                        'review_status' => CatalogImportItem::STATUS_NEEDS_REVIEW,
                        'warnings' => $draft->warnings,
                    ]);
                    $itemModels[$externalId] = $item;
                    $this->attachAttributes($item, $product['attributes'], $attributeVisibility);
                }

                foreach ($package->memberships as $membership) {
                    $itemModels[$membership['external_id']]->sources()->syncWithoutDetaching([
                        $sourceModels[$membership['source_slug']]->id,
                    ]);
                }

                return $run->fresh();
            }, 3);
        } catch (Throwable $error) {
            try {
                $this->compensateCreatedFiles($package, $disk, $createdFiles);
            } catch (Throwable $cleanupError) {
                throw CatalogImportOperationalException::for('cleanup_manual', $cleanupError);
            }
            throw $error;
        }
    }

    /**
     * @param  array<int, array{path: string, sha256: string, byte_length: int}>  $createdFiles
     * @return array<string, string>
     */
    private function copyImages(
        ValidatedCatalogImportPackage $package,
        FilesystemAdapter $disk,
        array &$createdFiles,
    ): array {
        $destinations = [];
        foreach ($package->images as $image) {
            $externalId = $image['external_id'];
            $relativePath = 'catalog-imports/'.$package->runId.'/images/'.$externalId.'.webp';
            $this->copyVerifiedImage($image, $relativePath, $disk, $createdFiles);
            $destinations[$externalId] = $relativePath;
        }

        return $destinations;
    }

    /** @param  array<int, array{path: string, sha256: string, byte_length: int}>  $createdFiles */
    private function copyVerifiedImage(
        array $image,
        string $relativePath,
        FilesystemAdapter $disk,
        array &$createdFiles,
    ): void {
        $sourcePath = $image['absolute_path'];
        $expectedHash = $image['sha256'];
        $expectedLength = $image['byte_length'];
        clearstatcache(true, $sourcePath);
        if (filesize($sourcePath) !== $expectedLength
            || ! hash_equals($expectedHash, hash_file('sha256', $sourcePath))) {
            throw CatalogImportOperationalException::for('source_changed_after_validation');
        }

        $this->assertSafePrivateDestination($disk, $relativePath);
        $disk->makeDirectory(dirname($relativePath));
        $destinationPath = $this->assertSafePrivateDestination($disk, $relativePath);
        if (file_exists($destinationPath)) {
            $this->assertDestinationImage($destinationPath, $expectedHash, $expectedLength);

            return;
        }

        $input = fopen($sourcePath, 'rb');
        if ($input === false) {
            throw CatalogImportOperationalException::for('source_open_failed');
        }
        $output = @fopen($destinationPath, 'xb');
        if ($output === false) {
            fclose($input);
            $this->assertDestinationImage($destinationPath, $expectedHash, $expectedLength);

            return;
        }
        $createdFiles[] = [
            'path' => $relativePath,
            'sha256' => $expectedHash,
            'byte_length' => $expectedLength,
        ];
        try {
            $copied = stream_copy_to_stream($input, $output);
            if ($copied !== $expectedLength || ! fflush($output)) {
                throw CatalogImportOperationalException::for('image_copy_failed');
            }
        } finally {
            fclose($input);
            fclose($output);
        }

        clearstatcache(true, $sourcePath);
        if (filesize($sourcePath) !== $expectedLength
            || ! hash_equals($expectedHash, hash_file('sha256', $sourcePath))) {
            throw CatalogImportOperationalException::for('source_changed_during_copy');
        }
        $this->assertDestinationImage($destinationPath, $expectedHash, $expectedLength);
    }

    private function assertDestinationImage(string $path, string $expectedHash, int $expectedLength): void
    {
        clearstatcache(true, $path);
        $stats = @lstat($path);
        if ($stats === false || is_link($path) || (($stats['mode'] ?? 0) & 0170000) !== 0100000
            || ($stats['nlink'] ?? 1) !== 1 || filesize($path) !== $expectedLength
            || ! hash_equals($expectedHash, hash_file('sha256', $path))) {
            throw CatalogImportOperationalException::for('destination_conflict');
        }
    }

    private function assertSafePrivateDestination(
        FilesystemAdapter $disk,
        string $relativePath,
    ): string {
        if (! preg_match('/^catalog-imports\/[a-z0-9][a-z0-9._-]{0,127}\/images\/\d{1,32}\.webp$/iD', $relativePath)) {
            throw CatalogImportOperationalException::for('destination_layout');
        }
        $rootPath = rtrim($disk->path(''), '\\/');
        $rootReal = realpath($rootPath);
        if ($rootReal === false || ! is_dir($rootReal)) {
            throw CatalogImportOperationalException::for('destination_root');
        }
        $segments = explode('/', dirname($relativePath));
        $current = $rootPath;
        $expectedReal = rtrim($rootReal, '\\/');
        foreach ($segments as $segment) {
            $current .= DIRECTORY_SEPARATOR.$segment;
            $expectedReal .= DIRECTORY_SEPARATOR.$segment;
            if (! file_exists($current) && ! is_link($current)) {
                continue;
            }
            $stats = @lstat($current);
            $currentReal = realpath($current);
            if ($stats === false || is_link($current)
                || (($stats['mode'] ?? 0) & 0170000) !== 0040000
                || $currentReal === false
                || $this->normalizedFilesystemPath($currentReal)
                    !== $this->normalizedFilesystemPath($expectedReal)) {
                throw CatalogImportOperationalException::for('destination_link');
            }
        }

        return $disk->path($relativePath);
    }

    private function normalizedFilesystemPath(string $path): string
    {
        $normalized = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);

        return DIRECTORY_SEPARATOR === '\\' ? strtolower($normalized) : $normalized;
    }

    /**
     * @param  array<string, array<int, string>>  $attributes
     * @param  array<string, bool>  $attributeVisibility
     */
    private function attachAttributes(
        CatalogImportItem $item,
        array $attributes,
        array $attributeVisibility,
    ): void {
        $sortOrder = 0;
        foreach ($attributes as $code => $values) {
            $isKnown = array_key_exists($code, TemplateProductRewriter::ATTRIBUTE_LABELS);
            $isPublic = $attributeVisibility[$code] ?? false;
            $expectedLabel = TemplateProductRewriter::ATTRIBUTE_LABELS[$code] ?? $code;
            $expectedType = self::ATTRIBUTE_TYPES[$code] ?? CatalogAttribute::TYPE_SELECT;
            $attribute = CatalogAttribute::query()->firstOrCreate(
                ['code' => $code],
                [
                    'label' => $expectedLabel,
                    'type' => $expectedType,
                    'sort_order' => ++$sortOrder,
                    'is_public' => $isPublic,
                ],
            );
            if (! $attribute->wasRecentlyCreated
                && ($attribute->label !== $expectedLabel
                    || $attribute->type !== $expectedType
                    || $attribute->is_public !== $isPublic)) {
                throw CatalogImportOperationalException::for('attribute_metadata_conflict');
            }
            $valueIds = [];
            foreach ($values as $value) {
                $normalizedValue = $this->normalizedAttributeValue($value);
                $numericValue = isset(self::ATTRIBUTE_TYPES[$code])
                    ? $this->numericAttributeValue($value)
                    : null;
                $expectedNumericValue = $this->normalizedNumericMetadata($numericValue);
                $attributeValue = $attribute->values()->firstOrCreate(
                    ['normalized_value' => $normalizedValue],
                    [
                        'label' => $value,
                        'numeric_value' => $numericValue,
                        'sort_order' => count($valueIds) + 1,
                    ],
                );
                if (! $attributeValue->wasRecentlyCreated
                    && ($attributeValue->label !== $value
                        || $attributeValue->numeric_value !== $expectedNumericValue)) {
                    throw CatalogImportOperationalException::for('attribute_value_conflict');
                }
                $valueIds[] = $attributeValue->id;
            }
            $item->attributeValues()->syncWithoutDetaching($valueIds);
        }
    }

    /** @return array<string, bool> */
    private function attributeVisibility(ValidatedCatalogImportPackage $package): array
    {
        $visibility = [];
        foreach ($package->products as $product) {
            foreach ($product['attributes'] as $code => $values) {
                $safe = array_key_exists($code, TemplateProductRewriter::ATTRIBUTE_LABELS);
                foreach ($values as $value) {
                    if ($this->publicTextSanitizer
                        ->sanitize($value, $product['source_price'])
                        ->wasModified()) {
                        $safe = false;
                        break;
                    }
                }
                $visibility[$code] = ($visibility[$code] ?? true) && $safe;
            }
        }

        return $visibility;
    }

    private function normalizedAttributeValue(string $value): string
    {
        $normalized = Str::slug(mb_strtolower($value));
        if ($normalized === '') {
            $normalized = 'value-'.substr(hash('sha256', $value), 0, 16);
        }

        return mb_substr($normalized, 0, 191);
    }

    private function numericAttributeValue(string $value): ?string
    {
        if (! preg_match('/-?\d+(?:[.,]\d+)?/', $value, $matches)) {
            return null;
        }

        return str_replace(',', '.', $matches[0]);
    }

    private function normalizedNumericMetadata(?string $value): ?string
    {
        return $value === null ? null : number_format((float) $value, 4, '.', '');
    }

    private function itemSlug(string $slugBase, string $externalId): string
    {
        $suffix = '-'.$externalId;
        $base = trim($slugBase, '-');
        if (str_ends_with($base, $suffix)) {
            $base = substr($base, 0, -strlen($suffix));
        }
        $base = rtrim(mb_substr($base, 0, 191 - strlen($suffix)), '-');

        return ($base !== '' ? $base : 'product').$suffix;
    }

    private function verifyExistingRun(
        CatalogImportRun $run,
        ValidatedCatalogImportPackage $package,
        FilesystemAdapter $disk,
    ): void {
        $envelope = $run->config;
        if (! is_array($envelope)
            || ($envelope['manifest_schema_version'] ?? null) !== $package->schemaVersion
            || ($envelope['config_schema_version'] ?? null) !== $package->configSchemaVersion
            || ($envelope['config_digest'] ?? null) !== $package->configDigest
            || ($envelope['manifest_digest'] ?? null) !== $package->manifestDigest
            || ($envelope['collector_request_count'] ?? null) !== $package->requestCount
            || ($envelope['collector_config'] ?? null) !== $package->config) {
            throw CatalogImportOperationalException::for('digest_conflict');
        }
        if ($run->source_count !== $package->counts['sources']
            || $run->unique_product_count !== $package->counts['products']
            || $run->image_count !== $package->counts['images']
            || $run->membership_count !== $package->counts['memberships']
            || $run->page_count !== $package->pageCount
            || $run->sources()->count() !== $package->counts['sources']
            || $run->items()->count() !== $package->counts['products']) {
            throw CatalogImportOperationalException::for('count_conflict');
        }
        $membershipsPerSource = array_count_values(array_column($package->memberships, 'source_slug'));
        $storedSources = $run->sources()->orderBy('sort_order')->get()->values();
        foreach ($package->sources as $index => $expected) {
            $stored = $storedSources[$index] ?? null;
            if (! $stored
                || $stored->label !== $expected['label']
                || $stored->source_url !== $expected['source_url']
                || $stored->target_slug !== $expected['target_slug']
                || $stored->enabled !== $expected['enabled']
                || $stored->status !== CatalogImportSource::STATUS_COMPLETED
                || $stored->sort_order !== $expected['sort_order']
                || $stored->pages_count !== $expected['pages']
                || $stored->items_count !== ($membershipsPerSource[$expected['target_slug']] ?? 0)
                || $stored->next_page_url !== null) {
                throw CatalogImportOperationalException::for('source_conflict');
            }
        }
        $storedItems = $run->items()->get()->keyBy('external_id');
        $attributeVisibility = $this->attributeVisibility($package);
        foreach ($package->products as $expected) {
            $stored = $storedItems->get($expected['external_id']);
            if (! $stored
                || $stored->provider !== 'rimskie.com'
                || $stored->source_url !== $expected['source_url']
                || $stored->source_title !== $expected['source_title']
                || $stored->source_description !== $expected['source_description']
                || $stored->source_price !== $expected['source_price']) {
                throw CatalogImportOperationalException::for('item_conflict');
            }
            $expectedAttributes = [];
            foreach ($expected['attributes'] as $code => $values) {
                foreach ($values as $value) {
                    $key = $code."\0".$this->normalizedAttributeValue($value);
                    $numericValue = isset(self::ATTRIBUTE_TYPES[$code])
                        ? $this->numericAttributeValue($value)
                        : null;
                    $expectedAttributes[$key] = [
                        'attribute_label' => TemplateProductRewriter::ATTRIBUTE_LABELS[$code] ?? $code,
                        'attribute_type' => self::ATTRIBUTE_TYPES[$code] ?? CatalogAttribute::TYPE_SELECT,
                        'attribute_is_public' => $attributeVisibility[$code] ?? false,
                        'value_label' => $value,
                        'numeric_value' => $this->normalizedNumericMetadata($numericValue),
                    ];
                }
            }
            ksort($expectedAttributes, SORT_STRING);
            $storedAttributes = DB::table('catalog_import_item_attribute_value as pivot')
                ->join('catalog_attribute_values as value', 'value.id', '=', 'pivot.attribute_value_id')
                ->join('catalog_attributes as attribute', 'attribute.id', '=', 'value.catalog_attribute_id')
                ->where('pivot.import_item_id', $stored->id)
                ->get([
                    'attribute.code',
                    'attribute.label as attribute_label',
                    'attribute.type as attribute_type',
                    'attribute.is_public as attribute_is_public',
                    'value.normalized_value',
                    'value.label as value_label',
                    'value.numeric_value',
                ])
                ->mapWithKeys(fn (object $record): array => [
                    $record->code."\0".$record->normalized_value => [
                        'attribute_label' => $record->attribute_label,
                        'attribute_type' => $record->attribute_type,
                        'attribute_is_public' => (bool) $record->attribute_is_public,
                        'value_label' => $record->value_label,
                        'numeric_value' => $this->normalizedNumericMetadata(
                            $record->numeric_value === null ? null : (string) $record->numeric_value,
                        ),
                    ],
                ])
                ->sortKeys()
                ->all();
            if (array_keys($storedAttributes) !== array_keys($expectedAttributes)) {
                throw CatalogImportOperationalException::for('attribute_set_conflict');
            }
            foreach ($expectedAttributes as $key => $metadata) {
                if ($storedAttributes[$key] !== $metadata) {
                    throw CatalogImportOperationalException::for('attribute_detail_conflict');
                }
            }
        }
        $itemIds = $run->items()->pluck('id');
        $membershipCount = DB::table('catalog_import_item_source')
            ->whereIn('import_item_id', $itemIds)
            ->count();
        if ($membershipCount !== $package->counts['memberships']) {
            throw CatalogImportOperationalException::for('membership_conflict');
        }
        $storedMemberships = DB::table('catalog_import_item_source as pivot')
            ->join('catalog_import_items as item', 'item.id', '=', 'pivot.import_item_id')
            ->join('catalog_import_sources as source', 'source.id', '=', 'pivot.import_source_id')
            ->where('item.catalog_import_run_id', $run->id)
            ->where('source.catalog_import_run_id', $run->id)
            ->get(['source.target_slug as source_slug', 'item.external_id'])
            ->map(static fn (object $record): string => $record->source_slug."\0".$record->external_id)
            ->sort()
            ->values()
            ->all();
        $expectedMemberships = array_map(
            static fn (array $record): string => $record['source_slug']."\0".$record['external_id'],
            $package->memberships,
        );
        sort($expectedMemberships, SORT_STRING);
        if ($storedMemberships !== $expectedMemberships) {
            throw CatalogImportOperationalException::for('membership_conflict');
        }
        $imagesById = $this->keyBy($package->images, 'external_id');
        foreach ($run->items as $item) {
            $image = $imagesById[$item->external_id] ?? null;
            $expectedPath = 'catalog-imports/'.$package->runId.'/images/'.$item->external_id.'.webp';
            if (! $image || $item->source_image_path !== $expectedPath
                || $item->source_image_sha256 !== $image['sha256']
                || $item->source_image_byte_length !== $image['byte_length']) {
                throw CatalogImportOperationalException::for('image_metadata_conflict');
            }
            $destinationPath = $this->assertSafePrivateDestination($disk, $expectedPath);
            $this->assertDestinationImage($destinationPath, $image['sha256'], $image['byte_length']);
        }
    }

    /** @return array<string, mixed> */
    private function configEnvelope(ValidatedCatalogImportPackage $package): array
    {
        return [
            'manifest_schema_version' => $package->schemaVersion,
            'config_schema_version' => $package->configSchemaVersion,
            'manifest_digest' => $package->manifestDigest,
            'config_digest' => $package->configDigest,
            'collector_request_count' => $package->requestCount,
            'collector_config' => $package->config,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $records
     * @return array<string, array<string, mixed>>
     */
    private function keyBy(array $records, string $key): array
    {
        $indexed = [];
        foreach ($records as $record) {
            $indexed[$record[$key]] = $record;
        }

        return $indexed;
    }

    /** @param  array<int, array{path: string, sha256: string, byte_length: int}>  $createdFiles */
    private function compensateCreatedFiles(
        ValidatedCatalogImportPackage $package,
        FilesystemAdapter $disk,
        array $createdFiles,
    ): void {
        if ($this->committedRunExists($package)) {
            return;
        }
        foreach ($createdFiles as $createdFile) {
            $destinationPath = $this->assertSafePrivateDestination($disk, $createdFile['path']);
            $this->assertDestinationImage(
                $destinationPath,
                $createdFile['sha256'],
                $createdFile['byte_length'],
            );
            if (! $this->deleteCompensationFile($disk, $createdFile['path'])) {
                throw CatalogImportOperationalException::for('cleanup_delete_failed');
            }
        }
    }

    protected function committedRunExists(ValidatedCatalogImportPackage $package): bool
    {
        return CatalogImportRun::query()
            ->where('provider', 'rimskie.com')
            ->where('external_run_id', $package->runId)
            ->exists();
    }

    protected function deleteCompensationFile(FilesystemAdapter $disk, string $relativePath): bool
    {
        return $disk->delete($relativePath);
    }
}
