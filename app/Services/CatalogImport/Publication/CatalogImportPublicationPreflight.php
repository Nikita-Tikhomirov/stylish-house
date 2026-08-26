<?php

namespace App\Services\CatalogImport\Publication;

use App\Data\CatalogImport\CatalogImportPublicationReport;
use App\Models\CatalogImportItem;
use App\Models\CatalogImportRun;
use App\Models\CatalogImportSource;
use Illuminate\Support\Facades\DB;
use JsonException;

final class CatalogImportPublicationPreflight
{
    public function __construct(
        private readonly CatalogImportImagePublisher $images,
        private readonly CatalogImportWarningAcknowledgement $warningAcknowledgement = new CatalogImportWarningAcknowledgement,
    ) {}

    public function inspect(
        CatalogImportRun $run,
        bool $allowProspectiveWarningAcknowledgement = false,
    ): CatalogImportPublicationReport {
        $run = $run->fresh() ?? $this->fail('run no longer exists');
        if (! in_array($run->status, [CatalogImportRun::STATUS_STAGED, CatalogImportRun::STATUS_REVIEWING], true)) {
            $this->fail('run must be staged or reviewing');
        }
        if ($run->provider !== 'rimskie.com') {
            $this->fail('provider must be rimskie.com');
        }
        if ($run->error_count !== 0 || $run->error !== null) {
            $this->fail('run, sources, and items must be error-free');
        }

        [$categoryId, $baseSubcategoryId] = $this->catalogRoots();
        $definitions = $this->canonicalDefinitions();
        $this->assertCollectorConfig($run, $definitions);

        $sources = $run->sources()->orderBy('sort_order')->get();
        if ($run->source_count !== 46 || $sources->count() !== 46
            || count($definitions) !== 46) {
            $this->fail('run must contain the exact 46 canonical source definitions');
        }
        if ($run->page_count !== $sources->sum('pages_count')
            || $run->duplicate_count !== 0
            || $sources->contains(static fn (CatalogImportSource $source): bool => $source->pages_count < 1)) {
            $this->fail('run page and duplicate counts must match the complete canonical collection');
        }
        foreach ($definitions as $index => $definition) {
            /** @var CatalogImportSource|null $source */
            $source = $sources->get($index);
            if ($source === null
                || $source->label !== $definition['label']
                || $source->source_url !== $definition['source_url']
                || $source->target_slug !== $definition['target_slug']
                || $source->sort_order !== $definition['sort_order']
                || ! $source->enabled) {
                $this->fail('run differs from the canonical source definitions');
            }
            if ($source->status !== CatalogImportSource::STATUS_COMPLETED
                || $source->review_status !== CatalogImportSource::REVIEW_APPROVED
                || $source->error !== null) {
                $this->fail('all sources must be completed, approved, and error-free');
            }
            if (! $this->hasPublicLandingCopy($source)) {
                $this->fail('all sources must contain complete rewritten landing copy');
            }
            if (DB::table('subcategories')->where('slug', $source->target_slug)->exists()) {
                $this->fail('target subcategory slug has an existing catalog owner');
            }
        }

        $items = $run->items()->orderBy('id')->get();
        if ($items->isEmpty() || $run->unique_product_count !== $items->count()
            || $run->image_count !== $items->count()) {
            $this->fail('run item and image counts are incomplete');
        }
        foreach ($items as $item) {
            if ($item->provider !== $run->provider
                || $item->review_status !== CatalogImportItem::STATUS_APPROVED
                || $item->error !== null) {
                $this->fail('run, sources, and items must be error-free and approved');
            }
            if (! $this->hasPublicProductCopy($item)) {
                $this->fail('all items must contain complete rewritten public copy');
            }
            if (! str_ends_with($item->rewritten_slug, '-'.$item->external_id)) {
                $this->fail('rewritten product slug must already end with its external ID suffix');
            }
            $this->images->verifyPrivate($run, $item);
            $this->images->assertPublicDestinationCompatible($run, $item);
            if (DB::table('products')->where('slug', $item->rewritten_slug)->exists()
                || DB::table('products')
                    ->where('source_provider', $item->provider)
                    ->where('source_external_id', $item->external_id)
                    ->exists()) {
                $this->fail('product slug or source identity has an existing catalog owner');
            }
        }

        $membershipCount = $this->assertMemberships($run, $sources->all(), $items->all());
        $publicAttributeMembershipCount = DB::table('catalog_import_item_attribute_value as pivot')
            ->join('catalog_import_items as item', 'item.id', '=', 'pivot.import_item_id')
            ->join('catalog_attribute_values as value', 'value.id', '=', 'pivot.attribute_value_id')
            ->join('catalog_attributes as attribute', 'attribute.id', '=', 'value.catalog_attribute_id')
            ->where('item.catalog_import_run_id', $run->id)
            ->where('attribute.is_public', true)
            ->count();

        $warningCount = $sources->sum(
            static fn (CatalogImportSource $source): int => count($source->warnings ?? []),
        ) + $items->sum(
            static fn (CatalogImportItem $item): int => count($item->warnings ?? []),
        );
        $warningsAcknowledged = $warningCount === 0 || (
            $run->warnings_acknowledged_at !== null
            && filled($run->warnings_acknowledged_by)
            && $this->warningAcknowledgement->matches($run)
        );
        $acknowledgementRequired = ! $warningsAcknowledged;
        if ($acknowledgementRequired && ! $allowProspectiveWarningAcknowledgement) {
            $this->fail('rewrite warnings must be explicitly acknowledged');
        }

        return new CatalogImportPublicationReport(
            sourceCount: $sources->count(),
            itemCount: $items->count(),
            membershipCount: $membershipCount,
            publicAttributeMembershipCount: $publicAttributeMembershipCount,
            warningCount: $warningCount,
            warningsAcknowledgementRequired: $acknowledgementRequired,
            categoryId: $categoryId,
            baseSubcategoryId: $baseSubcategoryId,
        );
    }

    /** @return array{int, int} */
    private function catalogRoots(): array
    {
        $category = DB::table('categories')->where('slug', 'story')->first();
        if ($category === null || ! (bool) ($category->show_in_catalog ?? false)) {
            $this->fail('visible category story is required');
        }
        $subcategory = DB::table('subcategories')->where('slug', 'rimskieshtory')->first();
        if ($subcategory === null || (int) $subcategory->category_id !== (int) $category->id
            || ! (bool) ($subcategory->show_in_catalog ?? false)) {
            $this->fail('visible base subcategory rimskieshtory is required under story');
        }

        return [(int) $category->id, (int) $subcategory->id];
    }

    /** @return array<int, array<string, mixed>> */
    private function canonicalDefinitions(): array
    {
        $path = (string) config(
            'catalog-import-publication.source_definitions',
            base_path('config/rimskie-import-sources.json'),
        );
        $contents = is_file($path) ? file_get_contents($path) : false;
        try {
            $definitions = is_string($contents)
                ? json_decode($contents, true, 512, JSON_THROW_ON_ERROR)
                : null;
        } catch (JsonException) {
            $definitions = null;
        }
        if (! is_array($definitions) || ! array_is_list($definitions)) {
            $this->fail('canonical source definition file is unavailable');
        }

        return $definitions;
    }

    /** @param array<int, array<string, mixed>> $definitions */
    private function assertCollectorConfig(CatalogImportRun $run, array $definitions): void
    {
        $collectorConfig = is_array($run->config) ? ($run->config['collector_config'] ?? null) : null;
        $limits = is_array($collectorConfig) ? ($collectorConfig['limits'] ?? null) : null;
        if (! is_array($limits)
            || ! array_key_exists('max_requests', $limits)
            || ! array_key_exists('max_products', $limits)
            || $limits['max_requests'] !== null
            || $limits['max_products'] !== null) {
            $this->fail('bounded collector run cannot be published');
        }
        $configuredSources = $collectorConfig['sources'] ?? null;
        if (! is_array($configuredSources) || ! array_is_list($configuredSources)
            || count($configuredSources) !== count($definitions)) {
            $this->fail('collector config must contain the exact 46 canonical source definitions');
        }
        foreach ($definitions as $index => $definition) {
            $configured = $configuredSources[$index] ?? null;
            if (! is_array($configured)
                || ($configured['label'] ?? null) !== $definition['label']
                || ($configured['sourceSlug'] ?? null) !== $definition['target_slug']
                || ($configured['sourceUrl'] ?? null) !== $definition['source_url']
                || ($configured['enabled'] ?? null) !== true
                || ($configured['sortOrder'] ?? null) !== $definition['sort_order']) {
                $this->fail('collector config differs from canonical source definitions');
            }
        }
    }

    private function hasPublicLandingCopy(CatalogImportSource $source): bool
    {
        foreach (['rewritten_title', 'rewritten_h1', 'rewritten_intro', 'rewritten_description', 'rewritten_seo'] as $field) {
            if (! is_string($source->{$field}) || trim($source->{$field}) === '') {
                return false;
            }
        }

        return true;
    }

    private function hasPublicProductCopy(CatalogImportItem $item): bool
    {
        foreach (['rewritten_title', 'rewritten_summary', 'rewritten_description', 'rewritten_slug'] as $field) {
            if (! is_string($item->{$field}) || trim($item->{$field}) === '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<int, CatalogImportSource>  $sources
     * @param  array<int, CatalogImportItem>  $items
     */
    private function assertMemberships(CatalogImportRun $run, array $sources, array $items): int
    {
        $sourceIds = array_map(static fn (CatalogImportSource $source): int => $source->id, $sources);
        $itemIds = array_map(static fn (CatalogImportItem $item): int => $item->id, $items);
        $memberships = DB::table('catalog_import_item_source')->whereIn('import_item_id', $itemIds)->get();
        if ($memberships->count() !== $run->membership_count) {
            $this->fail('membership count differs from the reviewed run');
        }
        $sourceCounts = array_fill_keys($sourceIds, 0);
        $itemCounts = array_fill_keys($itemIds, 0);
        foreach ($memberships as $membership) {
            if (! array_key_exists((int) $membership->import_source_id, $sourceCounts)
                || ! array_key_exists((int) $membership->import_item_id, $itemCounts)) {
                $this->fail('membership crosses catalog import run ownership');
            }
            $sourceCounts[(int) $membership->import_source_id]++;
            $itemCounts[(int) $membership->import_item_id]++;
        }
        foreach ($sources as $source) {
            if ($sourceCounts[$source->id] < 1 || $sourceCounts[$source->id] !== $source->items_count) {
                $this->fail('every canonical source must have its exact nonempty reviewed membership set');
            }
        }
        if (in_array(0, $itemCounts, true)) {
            $this->fail('every item must belong to an approved source');
        }

        $crossRunCount = DB::table('catalog_import_item_source as pivot')
            ->join('catalog_import_items as item', 'item.id', '=', 'pivot.import_item_id')
            ->join('catalog_import_sources as source', 'source.id', '=', 'pivot.import_source_id')
            ->where(function ($query) use ($itemIds, $sourceIds): void {
                $query->whereIn('pivot.import_item_id', $itemIds)
                    ->orWhereIn('pivot.import_source_id', $sourceIds);
            })
            ->where(function ($query) use ($run): void {
                $query->where('item.catalog_import_run_id', '!=', $run->id)
                    ->orWhere('source.catalog_import_run_id', '!=', $run->id);
            })
            ->count();
        if ($crossRunCount !== 0) {
            $this->fail('membership crosses catalog import run ownership');
        }

        return $memberships->count();
    }

    private function fail(string $message): never
    {
        throw new CatalogImportPublicationException('Catalog import publication preflight failed: '.$message.'.');
    }
}
