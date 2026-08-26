<?php

namespace App\Services\CatalogImport\Publication;

use App\Data\CatalogImport\CatalogImportRollbackResult;
use App\Data\CatalogImport\DatabaseBackupRequest;
use App\Data\CatalogImport\QuarantinedCatalogImportImage;
use App\Models\CatalogImportRun;
use App\Services\CatalogImport\DatabaseBackup\DatabaseBackupService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use JsonException;
use Throwable;

final class CatalogImportRollback
{
    /** @var array<int, array{0: string, 1: string}> */
    private const SUBCATEGORY_REFERENCE_COLUMNS = [
        ['products', 'subcategory_id'],
        ['faqstable', 'subcategory_id'],
        ['video_reviews', 'subcategory_id'],
        ['work_examples', 'subcategory_id'],
        ['subcategory_installation_types', 'subcategory_id'],
        ['price_recalc_runs', 'subcategory_id'],
        ['subcategories', 'clone_subcategory_id'],
    ];

    /** @var array<int, array{0: string, 1: string}> */
    private const PRODUCT_REFERENCE_COLUMNS = [
        ['favorites', 'product_id'],
        ['tabs', 'product_id'],
        ['first_screen_sliders', 'product_id'],
        ['price_recalc_run_items', 'product_id'],
    ];

    private readonly LaravelCatalogImportTransaction $transaction;

    private readonly CatalogImportRollbackStateClassifier $stateClassifier;

    public function __construct(
        private readonly DatabaseBackupService $backup,
        private readonly CatalogImportImagePublisher $images,
        private readonly CatalogImportMutationLock $lock,
        private readonly CatalogImportSitemapGenerator $sitemap,
        private readonly CatalogImportPublicationState $state = new CatalogImportPublicationState,
        private readonly CatalogImportDatabaseBackupVerifier $backupVerifier = new CatalogImportDatabaseBackupVerifier,
        ?LaravelCatalogImportTransaction $transaction = null,
        ?CatalogImportRollbackStateClassifier $stateClassifier = null,
    ) {
        $this->transaction = $transaction ?? new LaravelCatalogImportTransaction;
        $this->stateClassifier = $stateClassifier ?? new CatalogImportRollbackStateClassifier;
    }

    public function rollback(CatalogImportRun $run): CatalogImportRollbackResult
    {
        return $this->lock->synchronized(function () use ($run): CatalogImportRollbackResult {
            $current = $run->fresh();
            if ($current === null) {
                throw new CatalogImportPublicationException('Catalog import run no longer exists.');
            }
            if ($current->status === CatalogImportRun::STATUS_ROLLED_BACK) {
                return $this->finishCommittedRollback($current, true);
            }
            if ($current->status !== CatalogImportRun::STATUS_PUBLISHED) {
                throw new CatalogImportPublicationException('Only a published catalog import run can be rolled back.');
            }

            $hasJournal = $current->rollback_journal !== null;
            $snapshots = $this->assertPublishedState($current, ! $hasJournal);
            $expectedPlan = $this->images->expectedQuarantinePlan($current, $snapshots['media']);
            if ($hasJournal) {
                $plan = $this->journalPlan($current, $expectedPlan);
            } else {
                $plan = $this->images->planQuarantine($current, $snapshots['media']);
            }

            $current = $this->ensureRollbackBackup($current, ! $hasJournal);
            if ($hasJournal) {
                $plan = $this->journalPlan($current, $expectedPlan);
            } else {
                $this->persistJournal($current, $plan);
                $current = $this->freshRun($current);
            }

            try {
                $this->transaction->begin();
                $lockedRun = CatalogImportRun::query()->whereKey($current->id)->lockForUpdate()->firstOrFail();
                if ($lockedRun->status !== CatalogImportRun::STATUS_PUBLISHED) {
                    throw new CatalogImportPublicationException('Catalog import rollback state changed before mutation.');
                }
                $this->lockOwnedRows($lockedRun, $snapshots);
                $snapshots = $this->assertPublishedState($lockedRun, false);
                $expectedPlan = $this->images->expectedQuarantinePlan($lockedRun, $snapshots['media']);
                $plan = $this->journalPlan($lockedRun, $expectedPlan);
                $this->images->quarantinePlanned($plan);
                $this->deleteOwnedCatalog($lockedRun, $snapshots);
                $lockedRun->update([
                    'status' => CatalogImportRun::STATUS_ROLLED_BACK,
                    'rolled_back_at' => now(),
                    'rollback_error' => null,
                ]);
                $this->transaction->commit();
            } catch (Throwable $error) {
                $this->transaction->rollBackIfActive();

                return $this->reconcileFailedTransaction($current, $snapshots, $plan, $error);
            }

            return $this->finishCommittedRollback($this->freshRun($current), false);
        });
    }

    /**
     * @return array{
     *   product_ids: array<int, int>,
     *   subcategory_ids: array<int, int>,
     *   collection_pivots: array<int, array<string, mixed>>,
     *   attribute_pivots: array<int, array<string, mixed>>,
     *   media: array<int, array<string, mixed>>
     * }
     */
    private function assertPublishedState(CatalogImportRun $run, bool $verifyMedia): array
    {
        $productIds = [];
        $subcategoryIds = [];
        $collectionPivots = [];
        $attributePivots = [];
        $media = [];
        $sources = $run->sources()->orderBy('id')->get();
        if ($run->source_count !== 46 || $sources->count() !== 46) {
            throw new CatalogImportPublicationException('Published source ownership set is incomplete.');
        }
        foreach ($sources as $source) {
            $snapshot = $source->publication_snapshot;
            if (! is_array($snapshot) || ($snapshot['version'] ?? null) !== 1
                || ($snapshot['entity'] ?? null) !== 'subcategory'
                || ($snapshot['created'] ?? null) !== true
                || $source->created_subcategory !== true
                || $source->published_subcategory_id !== ($snapshot['entity_id'] ?? null)) {
                throw new CatalogImportPublicationException('Published source ownership snapshot is invalid.');
            }
            $this->state->assertSnapshotRow('subcategories', $snapshot);
            if (($snapshot['row']['import_run_id'] ?? null) !== $run->id) {
                throw new CatalogImportPublicationException('Published subcategory lost run ownership.');
            }
            $subcategoryIds[] = (int) $snapshot['entity_id'];
        }
        $items = $run->items()->orderBy('id')->get();
        if ($run->unique_product_count < 1 || $items->count() !== $run->unique_product_count
            || $run->image_count !== $items->count()) {
            throw new CatalogImportPublicationException('Published product ownership set is incomplete.');
        }
        foreach ($items as $item) {
            $snapshot = $item->publication_snapshot;
            if (! is_array($snapshot) || ($snapshot['version'] ?? null) !== 1
                || ($snapshot['entity'] ?? null) !== 'product'
                || ($snapshot['created'] ?? null) !== true
                || $item->created_product !== true
                || $item->published_product_id !== ($snapshot['entity_id'] ?? null)) {
                throw new CatalogImportPublicationException('Published item ownership snapshot is invalid.');
            }
            $this->state->assertSnapshotRow('products', $snapshot);
            if (($snapshot['row']['import_run_id'] ?? null) !== $run->id) {
                throw new CatalogImportPublicationException('Published product lost run ownership.');
            }
            $productId = (int) $snapshot['entity_id'];
            $currentCollections = $this->state->pivots(
                'catalog_collection_product',
                'product_id',
                $productId,
            );
            $currentAttributes = $this->state->pivots(
                'catalog_product_attribute_value',
                'product_id',
                $productId,
            );
            $snapshotCollections = $snapshot['collection_pivots'] ?? null;
            $snapshotAttributes = $snapshot['attribute_pivots'] ?? null;
            if (! is_array($snapshotCollections) || ! is_array($snapshotAttributes)
                || ! $this->state->equivalent($currentCollections, $snapshotCollections)
                || ! $this->state->equivalent($currentAttributes, $snapshotAttributes)) {
                throw new CatalogImportPublicationException(
                    'Published catalog pivot ownership changed after publication.'
                );
            }
            foreach ([...$currentCollections, ...$currentAttributes] as $pivot) {
                if (($pivot['catalog_import_run_id'] ?? null) !== $run->id) {
                    throw new CatalogImportPublicationException('Published catalog pivot ownership crosses runs.');
                }
            }
            $attributeValueIds = array_map(
                static fn (array $pivot): int => (int) $pivot['attribute_value_id'],
                $currentAttributes,
            );
            $snapshotMetadata = $snapshot['attribute_metadata'] ?? null;
            if (! is_array($snapshotMetadata)
                || ! $this->state->equivalent(
                    $this->state->attributeMetadata($attributeValueIds),
                    $snapshotMetadata,
                )) {
                throw new CatalogImportPublicationException(
                    'Published catalog attribute metadata changed after publication.'
                );
            }
            $sourceExternalId = $snapshot['row']['source_external_id'] ?? null;
            if (! is_string($sourceExternalId) || ! preg_match('/^\d{1,32}$/D', $sourceExternalId)) {
                throw new CatalogImportPublicationException('Published product media ownership is invalid.');
            }
            $expectedMediaPath = 'catalog-imports/'.$run->external_run_id.'/images/'.$sourceExternalId.'.webp';
            $mediaSnapshots = $snapshot['media'] ?? null;
            if (! is_array($mediaSnapshots) || ! array_is_list($mediaSnapshots)
                || count($mediaSnapshots) !== 1) {
                throw new CatalogImportPublicationException('Published product media ownership is invalid.');
            }
            foreach ($mediaSnapshots as $image) {
                if (! is_array($image) || ($image['relative_path'] ?? null) !== $expectedMediaPath) {
                    throw new CatalogImportPublicationException('Published product media ownership is invalid.');
                }
                if ($verifyMedia) {
                    $this->images->assertOwnedPublishedSnapshot($image);
                }
                $media[] = $image;
            }
            $productIds[] = $productId;
            $collectionPivots = [...$collectionPivots, ...$currentCollections];
            $attributePivots = [...$attributePivots, ...$currentAttributes];
        }
        sort($productIds, SORT_NUMERIC);
        sort($subcategoryIds, SORT_NUMERIC);
        if (count($media) !== $run->image_count
            || count($collectionPivots) !== $run->membership_count) {
            throw new CatalogImportPublicationException('Published catalog ownership counts changed.');
        }
        $this->assertNoExternalSubcategoryReferences($subcategoryIds, $collectionPivots);
        $this->assertNoExternalProductReferences($productIds);
        $ownedProductIds = DB::table('products')
            ->where('import_run_id', $run->id)
            ->orderBy('id')
            ->pluck('id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();
        $ownedSubcategoryIds = DB::table('subcategories')
            ->where('import_run_id', $run->id)
            ->orderBy('id')
            ->pluck('id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();
        if ($ownedProductIds !== $productIds || $ownedSubcategoryIds !== $subcategoryIds) {
            throw new CatalogImportPublicationException('Published catalog run ownership set changed.');
        }

        return [
            'product_ids' => $productIds,
            'subcategory_ids' => $subcategoryIds,
            'collection_pivots' => $collectionPivots,
            'attribute_pivots' => $attributePivots,
            'media' => $media,
        ];
    }

    /** @param array<string, mixed> $snapshots */
    private function lockOwnedRows(CatalogImportRun $run, array $snapshots): void
    {
        $run->sources()->orderBy('id')->lockForUpdate()->get();
        $run->items()->orderBy('id')->lockForUpdate()->get();
        DB::table('products')->whereIn('id', $snapshots['product_ids'])->orderBy('id')->lockForUpdate()->get();
        DB::table('subcategories')->whereIn('id', $snapshots['subcategory_ids'])->orderBy('id')->lockForUpdate()->get();
        $this->lockSubcategoryReferences($snapshots['subcategory_ids']);
        $this->lockProductReferences($snapshots['product_ids']);
        DB::table('catalog_collection_product')
            ->whereIn('product_id', $snapshots['product_ids'])
            ->orderBy('product_id')
            ->orderBy('subcategory_id')
            ->lockForUpdate()
            ->get();
        DB::table('catalog_product_attribute_value')
            ->whereIn('product_id', $snapshots['product_ids'])
            ->orderBy('product_id')
            ->orderBy('attribute_value_id')
            ->lockForUpdate()
            ->get();
        $valueIds = array_values(array_unique(array_map(
            static fn (array $pivot): int => (int) $pivot['attribute_value_id'],
            $snapshots['attribute_pivots'],
        )));
        sort($valueIds, SORT_NUMERIC);
        $attributeIds = DB::table('catalog_attribute_values')
            ->whereIn('id', $valueIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->pluck('catalog_attribute_id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->unique()
            ->sort()
            ->values()
            ->all();
        DB::table('catalog_attributes')->whereIn('id', $attributeIds)->orderBy('id')->lockForUpdate()->get();
    }

    /**
     * @param  array<int, int>  $subcategoryIds
     * @param  array<int, array<string, mixed>>  $expectedCollectionPivots
     */
    private function assertNoExternalSubcategoryReferences(
        array $subcategoryIds,
        array $expectedCollectionPivots,
    ): void {
        $actualCollectionPivots = DB::table('catalog_collection_product')
            ->whereIn('subcategory_id', $subcategoryIds)
            ->orderBy('product_id')
            ->orderBy('subcategory_id')
            ->get()
            ->map(static fn (object $row): array => (array) $row)
            ->all();
        usort($expectedCollectionPivots, static fn (array $left, array $right): int => [
            (int) $left['product_id'],
            (int) $left['subcategory_id'],
        ] <=> [
            (int) $right['product_id'],
            (int) $right['subcategory_id'],
        ]);
        if (! $this->state->equivalent($actualCollectionPivots, $expectedCollectionPivots)) {
            throw new CatalogImportPublicationException(
                'Published import subcategory is referenced outside its owned collection pivots.'
            );
        }

        foreach (self::SUBCATEGORY_REFERENCE_COLUMNS as [$table, $column]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }
            if (DB::table($table)->whereIn($column, $subcategoryIds)->exists()) {
                throw new CatalogImportPublicationException(
                    'Published import subcategory is referenced outside the import run.'
                );
            }
        }

        if (! Schema::hasTable('subcategories')
            || ! Schema::hasColumn('subcategories', 'related_subcategory_ids')) {
            return;
        }
        $owned = array_fill_keys($subcategoryIds, true);
        foreach (DB::table('subcategories')->whereNotNull('related_subcategory_ids')->orderBy('id')->get() as $subcategory) {
            $references = $subcategory->related_subcategory_ids;
            try {
                $references = is_string($references)
                    ? json_decode($references, true, 512, JSON_THROW_ON_ERROR)
                    : $references;
            } catch (JsonException $error) {
                throw new CatalogImportPublicationException(
                    'Related subcategory ownership data is invalid; refusing rollback.',
                    0,
                    $error,
                );
            }
            if (! is_array($references)) {
                throw new CatalogImportPublicationException(
                    'Related subcategory ownership data is invalid; refusing rollback.'
                );
            }
            foreach ($references as $reference) {
                if (isset($owned[(int) $reference])) {
                    throw new CatalogImportPublicationException(
                        'Published import subcategory is referenced outside the import run.'
                    );
                }
            }
        }
    }

    /** @param array<int, int> $subcategoryIds */
    private function lockSubcategoryReferences(array $subcategoryIds): void
    {
        DB::table('catalog_collection_product')
            ->whereIn('subcategory_id', $subcategoryIds)
            ->orderBy('product_id')
            ->orderBy('subcategory_id')
            ->lockForUpdate()
            ->get();
        foreach (self::SUBCATEGORY_REFERENCE_COLUMNS as [$table, $column]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }
            DB::table($table)
                ->whereIn($column, $subcategoryIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
        }
        if (Schema::hasTable('subcategories')
            && Schema::hasColumn('subcategories', 'related_subcategory_ids')) {
            DB::table('subcategories')
                ->whereNotNull('related_subcategory_ids')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
        }
    }

    /** @param array<int, int> $productIds */
    private function assertNoExternalProductReferences(array $productIds): void
    {
        foreach (self::PRODUCT_REFERENCE_COLUMNS as [$table, $column]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }
            if (DB::table($table)->whereIn($column, $productIds)->exists()) {
                throw new CatalogImportPublicationException(
                    'Published import product is referenced outside the import run.'
                );
            }
        }

        $jsonColumns = array_values(array_filter(
            ['related_product_ids', 'alternative_product_ids'],
            static fn (string $column): bool => Schema::hasColumn('products', $column),
        ));
        if ($jsonColumns === []) {
            return;
        }
        $owned = array_fill_keys($productIds, true);
        foreach (DB::table('products')->orderBy('id')->get(['id', ...$jsonColumns]) as $product) {
            foreach ($jsonColumns as $column) {
                $references = $product->{$column};
                if ($references === null) {
                    continue;
                }
                try {
                    $references = is_string($references)
                        ? json_decode($references, true, 512, JSON_THROW_ON_ERROR)
                        : $references;
                } catch (JsonException $error) {
                    throw new CatalogImportPublicationException(
                        'Related product ownership data is invalid; refusing rollback.',
                        0,
                        $error,
                    );
                }
                if (! is_array($references)) {
                    throw new CatalogImportPublicationException(
                        'Related product ownership data is invalid; refusing rollback.'
                    );
                }
                foreach ($references as $reference) {
                    if (isset($owned[(int) $reference])) {
                        throw new CatalogImportPublicationException(
                            'Published import product is referenced outside the import run.'
                        );
                    }
                }
            }
        }
    }

    /** @param array<int, int> $productIds */
    private function lockProductReferences(array $productIds): void
    {
        foreach (self::PRODUCT_REFERENCE_COLUMNS as [$table, $column]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }
            DB::table($table)
                ->whereIn($column, $productIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
        }
        $jsonColumns = array_values(array_filter(
            ['related_product_ids', 'alternative_product_ids'],
            static fn (string $column): bool => Schema::hasColumn('products', $column),
        ));
        if ($jsonColumns !== []) {
            DB::table('products')->orderBy('id')->lockForUpdate()->get(['id', ...$jsonColumns]);
        }
    }

    /** @param array<string, mixed> $snapshots */
    private function deleteOwnedCatalog(CatalogImportRun $run, array $snapshots): void
    {
        $deletedCollectionPivots = DB::table('catalog_collection_product')
            ->whereIn('product_id', $snapshots['product_ids'])
            ->where('catalog_import_run_id', $run->id)
            ->delete();
        $deletedAttributePivots = DB::table('catalog_product_attribute_value')
            ->whereIn('product_id', $snapshots['product_ids'])
            ->where('catalog_import_run_id', $run->id)
            ->delete();
        if ($deletedCollectionPivots !== count($snapshots['collection_pivots'])
            || $deletedAttributePivots !== count($snapshots['attribute_pivots'])) {
            throw new CatalogImportPublicationException('Owned pivot deletion count changed during rollback.');
        }
        $deletedProducts = DB::table('products')
            ->whereIn('id', $snapshots['product_ids'])
            ->where('import_run_id', $run->id)
            ->delete();
        $deletedSubcategories = DB::table('subcategories')
            ->whereIn('id', $snapshots['subcategory_ids'])
            ->where('import_run_id', $run->id)
            ->delete();
        if ($deletedProducts !== count($snapshots['product_ids'])
            || $deletedSubcategories !== count($snapshots['subcategory_ids'])) {
            throw new CatalogImportPublicationException('Owned catalog deletion count changed during rollback.');
        }
    }

    /** @param array<int, QuarantinedCatalogImportImage> $plan */
    private function persistJournal(CatalogImportRun $run, array $plan): void
    {
        $updated = CatalogImportRun::query()
            ->whereKey($run->id)
            ->where('status', CatalogImportRun::STATUS_PUBLISHED)
            ->whereNull('rollback_journal')
            ->update([
                'rollback_journal' => json_encode([
                    'version' => 1,
                    'status' => 'planned',
                    'media' => array_map(
                        static fn (QuarantinedCatalogImportImage $image): array => $image->snapshot(),
                        $plan,
                    ),
                ], JSON_THROW_ON_ERROR),
                'rollback_error' => null,
                'updated_at' => now(),
            ]);
        if ($updated !== 1) {
            throw new CatalogImportPublicationException('Rollback journal could not be persisted before media mutation.');
        }
    }

    /**
     * @param  array<int, QuarantinedCatalogImportImage>  $expectedPlan
     * @return array<int, QuarantinedCatalogImportImage>
     */
    private function journalPlan(CatalogImportRun $run, array $expectedPlan): array
    {
        $journal = $run->rollback_journal;
        $journalKeys = is_array($journal) ? array_keys($journal) : [];
        sort($journalKeys, SORT_STRING);
        if (! is_array($journal) || $journalKeys !== ['media', 'status', 'version']
            || ($journal['version'] ?? null) !== 1 || ($journal['status'] ?? null) !== 'planned'
            || ! is_array($journal['media'] ?? null) || ! array_is_list($journal['media'])) {
            throw new CatalogImportPublicationException('Durable rollback media journal is invalid.');
        }
        try {
            $plan = array_map(
                static fn (mixed $entry): QuarantinedCatalogImportImage => is_array($entry)
                    ? QuarantinedCatalogImportImage::fromSnapshot($entry)
                    : throw new InvalidArgumentException('Rollback image journal entry is invalid.'),
                $journal['media'],
            );
        } catch (InvalidArgumentException $error) {
            throw new CatalogImportPublicationException('Durable rollback media journal is invalid.', 0, $error);
        }
        $actual = array_map(
            static fn (QuarantinedCatalogImportImage $image): array => $image->snapshot(),
            $plan,
        );
        $expected = array_map(
            static fn (QuarantinedCatalogImportImage $image): array => $image->snapshot(),
            $expectedPlan,
        );
        if (! $this->state->equivalent($actual, $expected)) {
            throw new CatalogImportPublicationException('Durable rollback media journal does not match publication ownership.');
        }

        return $plan;
    }

    /**
     * @param  array<string, mixed>  $snapshots
     * @param  array<int, QuarantinedCatalogImportImage>  $plan
     */
    private function reconcileFailedTransaction(
        CatalogImportRun $run,
        array $snapshots,
        array $plan,
        Throwable $error,
    ): CatalogImportRollbackResult {
        try {
            $classification = $this->stateClassifier->classify(
                $run,
                $snapshots['product_ids'],
                $snapshots['subcategory_ids'],
            );
        } catch (Throwable) {
            $classification = 'uncertain';
        }

        if ($classification === 'committed') {
            return $this->finishCommittedRollback($this->freshRun($run), false);
        }

        if ($classification === 'published') {
            try {
                $published = $this->freshRun($run);
                $this->assertPublishedState($published, false);
                $this->images->restoreQuarantined($plan);
                $published->update([
                    'rollback_journal' => null,
                    'rollback_error' => 'rollback_failed',
                ]);
            } catch (Throwable $restoreError) {
                $this->recordRollbackError($run, 'rollback_media_restore_failed');
                throw new CatalogImportPublicationException(
                    'Catalog import rollback failed and quarantined media could not be restored; manual verification is required.',
                    0,
                    $restoreError,
                );
            }

            throw new CatalogImportPublicationException(
                'Catalog import rollback failed; catalog state and public media were restored.',
                0,
                $error,
            );
        }

        $this->recordRollbackError($run, 'rollback_state_uncertain');
        throw new CatalogImportPublicationException(
            'Catalog import rollback commit state is uncertain; quarantined media and its durable journal were preserved for manual verification.',
            0,
            $error,
        );
    }

    private function finishCommittedRollback(
        CatalogImportRun $run,
        bool $noOp,
    ): CatalogImportRollbackResult {
        if ($run->status !== CatalogImportRun::STATUS_ROLLED_BACK) {
            throw new CatalogImportPublicationException('Catalog import rollback commit could not be confirmed.');
        }
        $this->backupVerifier->assertRecordedRollback($run);
        $identifiers = $this->snapshotIdentifiers($run);
        $this->assertRolledBackCatalogAbsent($run, $identifiers);
        if ($run->rollback_journal === null) {
            return $noOp
                ? $this->generateSitemap($run, true)
                : throw new CatalogImportPublicationException('Committed rollback lost its durable media journal.');
        }

        $expectedPlan = $this->images->expectedQuarantinePlan($run, $identifiers['media']);
        $plan = $this->journalPlan($run, $expectedPlan);
        try {
            $this->images->purgeQuarantined($plan);
            $run->update([
                'rollback_journal' => null,
                'rollback_error' => null,
            ]);
        } catch (Throwable) {
            $this->recordRollbackError($run, 'rollback_media_cleanup_failed');

            return $this->generateSitemap(
                $this->freshRun($run),
                $noOp,
                'rollback_media_cleanup_failed',
            );
        }

        return $this->generateSitemap($this->freshRun($run), $noOp);
    }

    private function ensureRollbackBackup(CatalogImportRun $run, bool $allowCreate): CatalogImportRun
    {
        $fields = [
            $run->rollback_backup_created_at,
            $run->rollback_backup_path,
            $run->rollback_backup_sha256,
            $run->rollback_backup_manifest_path,
            $run->rollback_backup_manifest_sha256,
            $run->rollback_backup_raw_sha256,
            $run->rollback_backup_raw_size,
            $run->rollback_backup_gzip_size,
        ];
        $present = count(array_filter($fields, static fn (mixed $value): bool => $value !== null));
        if (! $allowCreate) {
            if ($present !== count($fields)) {
                throw new CatalogImportPublicationException(
                    'Durable rollback journal has no complete verified rollback database backup.'
                );
            }
            $this->backupVerifier->assertRecordedRollback($run);

            return $run;
        }
        if ($present > 0) {
            if ($present !== count($fields)) {
                throw new CatalogImportPublicationException(
                    'Previous rollback database backup record is partial; refusing a new attempt.'
                );
            }
            $this->backupVerifier->assertRecordedRollback($run);
        }

        $verified = $this->backup->create(new DatabaseBackupRequest(
            runId: $run->external_run_id,
            provider: $run->provider,
            connectionName: (string) config('database.default'),
            connection: (array) config('database.connections.'.config('database.default'), []),
        ));
        $manifestSha256 = $this->backupVerifier->verifyCreated(
            $verified,
            $run->external_run_id,
            $run->provider,
        );
        $updated = CatalogImportRun::query()
            ->whereKey($run->id)
            ->where('status', CatalogImportRun::STATUS_PUBLISHED)
            ->whereNull('rollback_journal')
            ->update([
                'rollback_backup_created_at' => $verified->verifiedAt,
                'rollback_backup_path' => $verified->archivePath,
                'rollback_backup_sha256' => $verified->gzipSha256,
                'rollback_backup_manifest_path' => $verified->manifestPath,
                'rollback_backup_manifest_sha256' => $manifestSha256,
                'rollback_backup_raw_sha256' => $verified->rawSha256,
                'rollback_backup_raw_size' => $verified->rawSize,
                'rollback_backup_gzip_size' => $verified->gzipSize,
                'updated_at' => now(),
            ]);
        if ($updated !== 1) {
            throw new CatalogImportPublicationException(
                'Verified rollback database backup could not be recorded before mutation.'
            );
        }
        $fresh = $this->freshRun($run);
        $this->backupVerifier->assertRecordedRollback($fresh);

        return $fresh;
    }

    /**
     * Reads immutable snapshot identifiers without requiring the deleted catalog
     * rows, so a committed rollback can resume private-trash cleanup after a crash.
     *
     * @return array{product_ids: array<int, int>, subcategory_ids: array<int, int>, media: array<int, array<string, mixed>>}
     */
    private function snapshotIdentifiers(CatalogImportRun $run): array
    {
        $productIds = [];
        $subcategoryIds = [];
        $media = [];
        $sources = $run->sources()->orderBy('id')->get();
        if ($run->source_count !== 46 || $sources->count() !== 46) {
            throw new CatalogImportPublicationException('Rolled back source ownership set is incomplete.');
        }
        foreach ($sources as $source) {
            $snapshot = $source->publication_snapshot;
            if (! is_array($snapshot) || ($snapshot['version'] ?? null) !== 1
                || ($snapshot['entity'] ?? null) !== 'subcategory'
                || ($snapshot['created'] ?? null) !== true
                || ! is_int($snapshot['entity_id'] ?? null)
                || $source->created_subcategory !== true
                || $source->published_subcategory_id !== null) {
                throw new CatalogImportPublicationException('Rolled back source ownership snapshot is invalid.');
            }
            $subcategoryIds[] = $snapshot['entity_id'];
        }
        $items = $run->items()->orderBy('id')->get();
        if ($run->unique_product_count < 1 || $items->count() !== $run->unique_product_count
            || $run->image_count !== $items->count()) {
            throw new CatalogImportPublicationException('Rolled back product ownership set is incomplete.');
        }
        foreach ($items as $item) {
            $snapshot = $item->publication_snapshot;
            if (! is_array($snapshot) || ($snapshot['version'] ?? null) !== 1
                || ($snapshot['entity'] ?? null) !== 'product'
                || ($snapshot['created'] ?? null) !== true
                || ! is_int($snapshot['entity_id'] ?? null)
                || $item->created_product !== true
                || $item->published_product_id !== null) {
                throw new CatalogImportPublicationException('Rolled back item ownership snapshot is invalid.');
            }
            $productIds[] = $snapshot['entity_id'];
            foreach ($snapshot['media'] ?? [] as $image) {
                if (! is_array($image)) {
                    throw new CatalogImportPublicationException('Rolled back media snapshot is invalid.');
                }
                $media[] = $image;
            }
        }
        sort($productIds, SORT_NUMERIC);
        sort($subcategoryIds, SORT_NUMERIC);
        if (count($media) !== $run->image_count) {
            throw new CatalogImportPublicationException('Rolled back media ownership set is incomplete.');
        }

        return [
            'product_ids' => $productIds,
            'subcategory_ids' => $subcategoryIds,
            'media' => $media,
        ];
    }

    /** @param array<string, mixed> $identifiers */
    private function assertRolledBackCatalogAbsent(CatalogImportRun $run, array $identifiers): void
    {
        if (DB::table('products')->whereIn('id', $identifiers['product_ids'])->exists()
            || DB::table('subcategories')->whereIn('id', $identifiers['subcategory_ids'])->exists()
            || DB::table('products')->where('import_run_id', $run->id)->exists()
            || DB::table('subcategories')->where('import_run_id', $run->id)->exists()
            || DB::table('catalog_collection_product')->where('catalog_import_run_id', $run->id)->exists()
            || DB::table('catalog_product_attribute_value')->where('catalog_import_run_id', $run->id)->exists()) {
            throw new CatalogImportPublicationException(
                'Rolled back catalog ownership is not empty; refusing media cleanup.'
            );
        }
    }

    private function recordRollbackError(CatalogImportRun $run, string $diagnostic): void
    {
        try {
            $run->fresh()?->update(['rollback_error' => $diagnostic]);
        } catch (Throwable) {
            // The durable journal remains the primary recovery record if diagnostics cannot be written.
        }
    }

    private function freshRun(CatalogImportRun $run): CatalogImportRun
    {
        $fresh = $run->fresh();
        if ($fresh === null) {
            throw new CatalogImportPublicationException('Catalog import run no longer exists.');
        }

        return $fresh;
    }

    private function generateSitemap(
        CatalogImportRun $run,
        bool $noOp,
        ?string $existingDiagnostic = null,
    ): CatalogImportRollbackResult {
        try {
            $this->sitemap->generate();

            return new CatalogImportRollbackResult(true, true, $noOp, $existingDiagnostic);
        } catch (Throwable) {
            $run->update(['rollback_error' => 'sitemap_generation_failed']);

            return new CatalogImportRollbackResult(true, false, $noOp, 'sitemap_generation_failed');
        }
    }
}
