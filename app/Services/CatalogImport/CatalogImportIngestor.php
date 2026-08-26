<?php

namespace App\Services\CatalogImport;

use App\Data\CatalogImport\RewrittenLandingContent;
use App\Data\CatalogImport\RewrittenProductContent;
use App\Data\CatalogImport\ValidatedCatalogImportPackage;
use App\Models\CatalogAttribute;
use App\Models\CatalogImportItem;
use App\Models\CatalogImportRun;
use App\Models\CatalogImportSource;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class CatalogImportIngestor
{
    private ProductContentRewriter $productRewriter;

    private LandingContentRewriter $landingRewriter;

    /** @var array<string, string> */
    private const ATTRIBUTE_TYPES = [
        'width' => CatalogAttribute::TYPE_NUMBER,
        'height' => CatalogAttribute::TYPE_NUMBER,
        'density' => CatalogAttribute::TYPE_NUMBER,
    ];

    public function __construct(
        ?ProductContentRewriter $productRewriter = null,
        ?LandingContentRewriter $landingRewriter = null,
    ) {
        $this->productRewriter = $productRewriter ?? new TemplateProductRewriter;
        $this->landingRewriter = $landingRewriter ?? new TemplateLandingRewriter;
    }

    public function ingest(ValidatedCatalogImportPackage $package): CatalogImportRun
    {
        $disk = Storage::disk('local');
        $createdFiles = [];
        $landingDrafts = $this->rewriteLandings($package);
        $productDrafts = $this->rewriteProducts($package);
        $attributeVisibility = $this->attributeVisibility($package);

        try {
            return DB::transaction(function () use (
                $package,
                $disk,
                &$createdFiles,
                $landingDrafts,
                $productDrafts,
                $attributeVisibility,
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
            $this->compensateCreatedFiles($package, $disk, $createdFiles);
            throw $error;
        }
    }

    /**
     * @return array<string, RewrittenLandingContent>
     */
    private function rewriteLandings(ValidatedCatalogImportPackage $package): array
    {
        $drafts = [];
        $signatures = [];
        foreach ($package->sources as $source) {
            $slug = $source['target_slug'];
            $draft = $this->landingRewriter->rewrite($source['label'], $slug);
            $drafts[$slug] = $draft;
            $signature = $this->landingSignature($draft);
            $signatures[$signature][] = $slug;
        }
        foreach ($signatures as $slugs) {
            if (count($slugs) < 2) {
                continue;
            }
            foreach ($slugs as $slug) {
                $draft = $drafts[$slug];
                $warnings = [...$draft->warnings, 'duplicate_landing_copy'];
                $warnings = array_values(array_unique($warnings));
                sort($warnings, SORT_STRING);
                $drafts[$slug] = new RewrittenLandingContent(
                    title: $draft->title,
                    h1: $draft->h1,
                    intro: $draft->intro,
                    description: $draft->description,
                    seo: $draft->seo,
                    warnings: $warnings,
                );
            }
        }

        return $drafts;
    }

    /** @return array<string, RewrittenProductContent> */
    private function rewriteProducts(ValidatedCatalogImportPackage $package): array
    {
        $drafts = [];
        foreach ($package->products as $product) {
            $drafts[$product['external_id']] = $this->productRewriter->rewrite([
                'external_id' => $product['external_id'],
                'title' => $product['source_title'],
                'description' => $product['source_description'],
                'source_price' => $product['source_price'],
                'attributes' => $product['attributes'],
            ]);
        }

        return $drafts;
    }

    /**
     * @param  array<int, string>  $createdFiles
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

    /** @param  array<int, string>  $createdFiles */
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
            throw new RuntimeException('Source image changed after package validation.');
        }

        $disk->makeDirectory(dirname($relativePath));
        $destinationPath = $disk->path($relativePath);
        if (file_exists($destinationPath)) {
            $this->assertDestinationImage($destinationPath, $expectedHash, $expectedLength);

            return;
        }

        $input = fopen($sourcePath, 'rb');
        if ($input === false) {
            throw new RuntimeException('Validated source image cannot be opened for copying.');
        }
        $output = @fopen($destinationPath, 'xb');
        if ($output === false) {
            fclose($input);
            $this->assertDestinationImage($destinationPath, $expectedHash, $expectedLength);

            return;
        }
        $createdFiles[] = $relativePath;
        try {
            $copied = stream_copy_to_stream($input, $output);
            if ($copied !== $expectedLength || ! fflush($output)) {
                throw new RuntimeException('Private image copy did not write the expected bytes.');
            }
        } finally {
            fclose($input);
            fclose($output);
        }

        clearstatcache(true, $sourcePath);
        if (filesize($sourcePath) !== $expectedLength
            || ! hash_equals($expectedHash, hash_file('sha256', $sourcePath))) {
            throw new RuntimeException('Source image changed during the private copy.');
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
            throw new RuntimeException('Private destination contains a conflicting image.');
        }
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
                    || (! $isPublic && $attribute->is_public))) {
                throw new RuntimeException('Catalog attribute metadata collision for '.$code.'.');
            }
            $valueIds = [];
            foreach ($values as $value) {
                $normalizedValue = $this->normalizedAttributeValue($value);
                $numericValue = isset(self::ATTRIBUTE_TYPES[$code])
                    ? $this->numericAttributeValue($value)
                    : null;
                $attributeValue = $attribute->values()->firstOrCreate(
                    ['normalized_value' => $normalizedValue],
                    [
                        'label' => $value,
                        'numeric_value' => $numericValue,
                        'sort_order' => count($valueIds) + 1,
                    ],
                );
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
                $safe = array_key_exists($code, TemplateProductRewriter::ATTRIBUTE_LABELS)
                    && ! $this->containsBlockedAttributeValue($values, $product['source_price']);
                $visibility[$code] = ($visibility[$code] ?? true) && $safe;
            }
        }

        return $visibility;
    }

    /** @param  array<int, string>  $values */
    private function containsBlockedAttributeValue(array $values, string $sourcePrice): bool
    {
        $text = implode(' ', $values);
        $blocked = '/<[^>]+>|&(?:#\d+|#x[0-9a-f]+|[a-z][a-z0-9]+);|https?:\/\/|www\.|'
            .'[\p{L}\p{N}.+_-]+@[\p{L}\p{N}.-]+\.[\p{L}]{2,}|'
            .'(?:\+?7|8)[\s()\p{Pd}-]*\d{3}[\s()\p{Pd}-]*\d{3}[\s\p{Pd}-]*\d{2}[\s\p{Pd}-]*\d{2}|'
            .'\b(?:rimskie(?:\.com)?|kortin)\b|'
            .'\b(?:купить|заказать|доставк\p{L}*|лучш\p{L}*|гаранти\p{L}*|акци\p{L}*|'
            .'скидк\p{L}*|бесплатн\p{L}*|идеальн\p{L}*|премиальн\p{L}*)\b|'
            .'\d[\d\s\x{00A0}\x{2009}\x{202F}]*(?:[.,]\d{1,2})?\s*(?:₽|руб(?:\.|лей|ля)?)|'
            .'[\x{0000}-\x{001F}\x{007F}\x{200B}-\x{200F}\x{202A}-\x{202E}\x{2060}-\x{206F}\x{FEFF}]/ui';
        if (preg_match($blocked, $text) === 1) {
            return true;
        }
        $integerPrice = preg_replace('/\.00$/D', '', $sourcePrice) ?? $sourcePrice;
        $digits = implode('[\s\x{00A0}\x{2009}\x{202F}]*', str_split($integerPrice));

        return preg_match('/(?<!\d)'.$digits.'(?:[.,]00)?(?!\d|[.,]\d|\s*(?:мм|см|м\b))/ui', $text) === 1;
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

    private function landingSignature(RewrittenLandingContent $draft): string
    {
        $value = implode("\0", [
            $draft->title,
            $draft->h1,
            $draft->intro,
            $draft->description,
            $draft->seo,
        ]);
        $value = mb_strtolower($value);

        return preg_replace('/\s+/u', ' ', trim($value)) ?? trim($value);
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
            throw new RuntimeException('Existing catalog import run has a changed manifest or config digest.');
        }
        if ($run->source_count !== $package->counts['sources']
            || $run->unique_product_count !== $package->counts['products']
            || $run->image_count !== $package->counts['images']
            || $run->membership_count !== $package->counts['memberships']
            || $run->page_count !== $package->pageCount
            || $run->sources()->count() !== $package->counts['sources']
            || $run->items()->count() !== $package->counts['products']) {
            throw new RuntimeException('Existing catalog import run no longer matches validated counts.');
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
                throw new RuntimeException('Existing catalog import run has a conflicting immutable source.');
            }
        }
        $storedItems = $run->items()->get()->keyBy('external_id');
        foreach ($package->products as $expected) {
            $stored = $storedItems->get($expected['external_id']);
            if (! $stored
                || $stored->provider !== 'rimskie.com'
                || $stored->source_url !== $expected['source_url']
                || $stored->source_title !== $expected['source_title']
                || $stored->source_description !== $expected['source_description']
                || $stored->source_price !== $expected['source_price']) {
                throw new RuntimeException('Existing catalog import run has a conflicting immutable item.');
            }
            $expectedAttributes = [];
            foreach ($expected['attributes'] as $code => $values) {
                foreach ($values as $value) {
                    $expectedAttributes[] = $code."\0".$this->normalizedAttributeValue($value);
                }
            }
            $expectedAttributes = array_values(array_unique($expectedAttributes, SORT_STRING));
            sort($expectedAttributes, SORT_STRING);
            $storedAttributes = DB::table('catalog_import_item_attribute_value as pivot')
                ->join('catalog_attribute_values as value', 'value.id', '=', 'pivot.attribute_value_id')
                ->join('catalog_attributes as attribute', 'attribute.id', '=', 'value.catalog_attribute_id')
                ->where('pivot.import_item_id', $stored->id)
                ->get(['attribute.code', 'value.normalized_value'])
                ->map(static fn (object $record): string => $record->code."\0".$record->normalized_value)
                ->sort()
                ->values()
                ->all();
            if ($storedAttributes !== $expectedAttributes) {
                throw new RuntimeException('Existing catalog import item has a conflicting attribute set.');
            }
        }
        $itemIds = $run->items()->pluck('id');
        $membershipCount = DB::table('catalog_import_item_source')
            ->whereIn('import_item_id', $itemIds)
            ->count();
        if ($membershipCount !== $package->counts['memberships']) {
            throw new RuntimeException('Existing catalog import run has a conflicting membership set.');
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
            throw new RuntimeException('Existing catalog import run has a conflicting membership set.');
        }
        $imagesById = $this->keyBy($package->images, 'external_id');
        foreach ($run->items as $item) {
            $image = $imagesById[$item->external_id] ?? null;
            $expectedPath = 'catalog-imports/'.$package->runId.'/images/'.$item->external_id.'.webp';
            if (! $image || $item->source_image_path !== $expectedPath
                || $item->source_image_sha256 !== $image['sha256']
                || $item->source_image_byte_length !== $image['byte_length']) {
                throw new RuntimeException('Existing catalog import item has conflicting immutable image metadata.');
            }
            $this->assertDestinationImage($disk->path($expectedPath), $image['sha256'], $image['byte_length']);
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

    /** @param  array<int, string>  $createdFiles */
    private function compensateCreatedFiles(
        ValidatedCatalogImportPackage $package,
        FilesystemAdapter $disk,
        array $createdFiles,
    ): void {
        try {
            $committedRunExists = CatalogImportRun::query()
                ->where('provider', 'rimskie.com')
                ->where('external_run_id', $package->runId)
                ->exists();
        } catch (Throwable) {
            $committedRunExists = false;
        }
        if ($committedRunExists) {
            return;
        }
        foreach ($createdFiles as $relativePath) {
            $disk->delete($relativePath);
        }
    }
}
