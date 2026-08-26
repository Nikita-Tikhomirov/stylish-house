<?php

namespace App\Services\CatalogImport\Publication;

use App\Data\CatalogImport\CatalogImportPublicationResult;
use App\Data\CatalogImport\DatabaseBackupRequest;
use App\Data\CatalogImport\PublishedCatalogImportImage;
use App\Models\CatalogImportItem;
use App\Models\CatalogImportRun;
use App\Services\CatalogImport\DatabaseBackup\DatabaseBackupService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class CatalogImportPublisher
{
    private readonly CatalogImportDatabaseBackupVerifier $backupVerifier;

    private readonly CatalogImportPublicationStateClassifier $stateClassifier;

    public function __construct(
        private readonly CatalogImportPublicationPreflight $preflight,
        private readonly DatabaseBackupService $backup,
        private readonly CatalogImportImagePublisher $images,
        private readonly CatalogImportMutationLock $lock,
        private readonly CatalogImportSitemapGenerator $sitemap,
        private readonly CatalogImportPublicationState $state = new CatalogImportPublicationState,
        private readonly CatalogImportWarningAcknowledgement $warningAcknowledgement = new CatalogImportWarningAcknowledgement,
        ?CatalogImportDatabaseBackupVerifier $backupVerifier = null,
        ?CatalogImportPublicationStateClassifier $stateClassifier = null,
    ) {
        $this->backupVerifier = $backupVerifier ?? new CatalogImportDatabaseBackupVerifier;
        $this->stateClassifier = $stateClassifier ?? new CatalogImportPublicationStateClassifier;
    }

    public function publish(
        CatalogImportRun $run,
        ?string $warningsAcknowledgedBy = null,
    ): CatalogImportPublicationResult {
        if (config('catalog-import-publication.enabled') !== true) {
            throw new CatalogImportPublicationException(
                'Catalog publication is disabled; set RIMSKIE_IMPORT_PUBLICATION_ENABLED=true explicitly.'
            );
        }

        $operator = $warningsAcknowledgedBy === null
            ? null
            : $this->warningAcknowledgement->validatedOperator($warningsAcknowledgedBy);

        return $this->lock->synchronized(function () use ($run, $operator): CatalogImportPublicationResult {
            $current = $run->fresh();
            if ($current === null) {
                throw new CatalogImportPublicationException('Catalog import run no longer exists.');
            }
            if ($current->status === CatalogImportRun::STATUS_PUBLISHED) {
                $this->assertPublishedRunIntact($current);
                $this->backupVerifier->assertRecordedPublication($current);
                $this->finishPublicationJournal($current);

                return $this->generateSitemap($current->fresh(), true);
            }

            if ($current->publication_journal === null) {
                $report = $this->preflight->inspect($current, $operator !== null);
                $verifiedBackup = $this->backup->create(new DatabaseBackupRequest(
                    runId: $current->external_run_id,
                    provider: $current->provider,
                    connectionName: (string) config('database.default'),
                    connection: (array) config('database.connections.'.config('database.default'), []),
                ));
                $manifestSha256 = $this->backupVerifier->verifyCreated(
                    $verifiedBackup,
                    $current->external_run_id,
                    $current->provider,
                );
                $current->update([
                    'backup_created_at' => $verifiedBackup->verifiedAt,
                    'backup_path' => $verifiedBackup->archivePath,
                    'backup_sha256' => $verifiedBackup->gzipSha256,
                    'backup_manifest_path' => $verifiedBackup->manifestPath,
                    'backup_manifest_sha256' => $manifestSha256,
                    'backup_raw_sha256' => $verifiedBackup->rawSha256,
                    'backup_raw_size' => $verifiedBackup->rawSize,
                    'backup_gzip_size' => $verifiedBackup->gzipSize,
                    'publication_error' => null,
                ]);
                if ($operator !== null && $report->warningCount > 0) {
                    $this->warningAcknowledgement->acknowledge($current, $operator);
                    $current = $current->fresh() ?? throw new CatalogImportPublicationException(
                        'Catalog import run no longer exists after warning acknowledgement.'
                    );
                }
                $this->backupVerifier->assertRecordedPublication(
                    $current->fresh() ?? throw new CatalogImportPublicationException(
                        'Catalog import run no longer exists after backup recording.'
                    )
                );
                $this->preflight->inspect($current->fresh());
                $this->persistPublicationJournal($current->fresh());
                $current = $current->fresh() ?? throw new CatalogImportPublicationException(
                    'Catalog import run no longer exists after publication journal recording.'
                );
            } else {
                $this->backupVerifier->assertRecordedPublication($current);
                $this->preflight->inspect($current);
            }

            /** @var array<int, PublishedCatalogImportImage> $publishedImages */
            $publishedImages = [];
            try {
                $publishedImages = $this->preparePublicationImages($current);
                DB::transaction(function () use ($current, $publishedImages): void {
                    $lockedRun = CatalogImportRun::query()->whereKey($current->id)->lockForUpdate()->firstOrFail();
                    $this->lockPublicationRows($lockedRun);
                    $lockedReport = $this->preflight->inspect($lockedRun);
                    if ($lockedRun->backup_created_at === null
                        || $lockedRun->backup_path === null
                        || $lockedRun->backup_sha256 === null) {
                        throw new CatalogImportPublicationException(
                            'Verified backup must be recorded before catalog mutation.'
                        );
                    }
                    $this->backupVerifier->assertRecordedPublication($lockedRun);
                    foreach ($publishedImages as $publishedImage) {
                        $this->images->assertPublishedImage($publishedImage);
                    }

                    $this->publishCatalog(
                        $lockedRun,
                        $lockedReport->categoryId,
                        $lockedReport->baseSubcategoryId,
                        $publishedImages,
                    );
                }, 1);
                $this->finishPublicationJournal(
                    $current->fresh() ?? throw new CatalogImportPublicationException(
                        'Published catalog import run no longer exists.'
                    )
                );
            } catch (Throwable $error) {
                try {
                    $classification = $this->stateClassifier->classify($current);
                } catch (Throwable) {
                    $classification = 'uncertain';
                }
                if ($classification === 'committed') {
                    try {
                        $published = $current->fresh() ?? throw new CatalogImportPublicationException(
                            'Committed catalog import run no longer exists.'
                        );
                        $this->assertPublishedRunIntact($published);
                        $this->backupVerifier->assertRecordedPublication($published);
                        $this->finishPublicationJournal($published);

                        return $this->generateSitemap($published->fresh(), false);
                    } catch (Throwable) {
                        $classification = 'uncertain';
                    }
                }
                if ($classification !== 'uncommitted') {
                    $current->fresh()?->update([
                        'publication_error' => 'publication_state_uncertain',
                    ]);
                    throw new CatalogImportPublicationException(
                        'Catalog import publication commit state is uncertain; public media was preserved for manual verification.',
                        0,
                        $error,
                    );
                }
                if ($error instanceof CatalogImportManualVerificationException) {
                    $current->fresh()?->update([
                        'publication_error' => 'publication_media_verification_required',
                    ]);
                    throw new CatalogImportPublicationException(
                        'Catalog import publication stopped with preserved media evidence; manual verification is required.',
                        0,
                        $error,
                    );
                }
                try {
                    $compensationImages = $publishedImages === []
                        ? $this->journalPublishedImages($current->fresh())
                        : array_values($publishedImages);
                    $this->images->compensate($compensationImages);
                } catch (Throwable $compensationError) {
                    $current->fresh()?->update([
                        'publication_error' => 'publication_compensation_failed',
                    ]);
                    throw new CatalogImportPublicationException(
                        'Catalog import publication failed and created media could not be safely compensated; manual verification is required.',
                        0,
                        $compensationError,
                    );
                }
                $current->fresh()?->update([
                    'publication_error' => 'publication_failed',
                    'publication_journal' => null,
                ]);
                throw new CatalogImportPublicationException(
                    'Catalog import publication failed after the verified backup.',
                    0,
                    $error,
                );
            }

            return $this->generateSitemap($current->fresh(), false);
        });
    }

    private function persistPublicationJournal(CatalogImportRun $run): void
    {
        if ($run->publication_journal !== null) {
            throw new CatalogImportPublicationException('Publication media journal is immutable once planned.');
        }
        $public = Storage::disk('public');
        $media = [];
        foreach ($run->items()->orderBy('id')->get() as $item) {
            $relativePath = $this->images->destination($run, $item);
            $this->images->assertPublicDestinationCompatible($run, $item);
            $media[] = [
                'item_id' => $item->id,
                'external_id' => $item->external_id,
                'relative_path' => $relativePath,
                'database_path' => 'storage/'.$relativePath,
                'sha256' => $item->source_image_sha256,
                'byte_length' => $item->source_image_byte_length,
                'was_absent' => ! $public->exists($relativePath),
                'created' => null,
                'creation_identity' => null,
                'status' => 'planned',
            ];
        }
        if ($media === []) {
            throw new CatalogImportPublicationException('Publication media journal cannot be empty.');
        }
        $run->update([
            'publication_journal' => [
                'version' => 1,
                'status' => 'planned',
                'media' => $media,
            ],
        ]);
    }

    /** @return array<int, PublishedCatalogImportImage> */
    private function preparePublicationImages(CatalogImportRun $run): array
    {
        [$journal, $entries, $items] = $this->publicationJournal($run);
        $published = [];
        foreach ($entries as $index => $entry) {
            $item = $items[$index];
            if ($entry['status'] === 'published') {
                $image = $this->imageFromJournal($entry);
                $this->images->assertPublishedImage($image);
                $published[$item->id] = $image;

                continue;
            }

            $this->images->assertPublicDestinationCompatible($run, $item);
            $exists = Storage::disk('public')->exists($entry['relative_path']);
            if ($entry['was_absent'] && $exists) {
                throw new CatalogImportManualVerificationException(
                    'A public image appeared after the durable publication media plan.'
                );
            }
            if (! $entry['was_absent'] && ! $exists) {
                throw new CatalogImportManualVerificationException(
                    'A pre-existing public image disappeared after the durable publication media plan.'
                );
            }
            $image = $this->images->publish($run, $item);
            if ($image->created !== $entry['was_absent']) {
                throw new CatalogImportManualVerificationException(
                    'Public image creation ownership differs from the durable publication media plan.'
                );
            }
            $journal['media'][$index]['created'] = $image->created;
            $journal['media'][$index]['creation_identity'] = $image->creationIdentity;
            $journal['media'][$index]['status'] = 'published';
            if ($index === array_key_last($entries)) {
                $journal['status'] = 'ready';
            }
            try {
                $run->update(['publication_journal' => $journal]);
            } catch (Throwable $error) {
                throw new CatalogImportManualVerificationException(
                    'Created public media could not be recorded durably.',
                    0,
                    $error,
                );
            }
            $published[$item->id] = $image;
        }

        return $published;
    }

    /** @return array<int, PublishedCatalogImportImage> */
    private function journalPublishedImages(?CatalogImportRun $run): array
    {
        if ($run === null || $run->publication_journal === null) {
            return [];
        }
        [, $entries] = $this->publicationJournal($run);
        $images = [];
        foreach ($entries as $entry) {
            if ($entry['status'] === 'published') {
                $images[] = $this->imageFromJournal($entry);
            }
        }

        return $images;
    }

    private function finishPublicationJournal(CatalogImportRun $run): void
    {
        if ($run->publication_journal === null) {
            return;
        }
        [$journal, $entries, $items] = $this->publicationJournal($run);
        if ($journal['status'] !== 'ready') {
            throw new CatalogImportPublicationException(
                'Committed publication has an incomplete durable media journal.'
            );
        }
        foreach ($entries as $index => $entry) {
            if ($entry['status'] !== 'published') {
                throw new CatalogImportPublicationException(
                    'Committed publication has an incomplete durable media journal.'
                );
            }
            $snapshot = $items[$index]->publication_snapshot;
            $media = is_array($snapshot) ? ($snapshot['media'] ?? null) : null;
            if (! is_array($media) || count($media) !== 1
                || ! $this->state->equivalent($media[0], $this->imageFromJournal($entry)->snapshot())) {
                throw new CatalogImportPublicationException(
                    'Committed publication media journal does not match its immutable snapshot.'
                );
            }
            $this->images->assertPublishedImage($this->imageFromJournal($entry));
        }
        $run->update(['publication_journal' => null]);
    }

    /**
     * @return array{
     *   0: array{version: int, status: string, media: array<int, array<string, mixed>>},
     *   1: array<int, array<string, mixed>>,
     *   2: array<int, CatalogImportItem>
     * }
     */
    private function publicationJournal(CatalogImportRun $run): array
    {
        $journal = $run->publication_journal;
        $keys = is_array($journal) ? array_keys($journal) : [];
        sort($keys, SORT_STRING);
        if (! is_array($journal) || $keys !== ['media', 'status', 'version']
            || ($journal['version'] ?? null) !== 1
            || ! in_array($journal['status'] ?? null, ['planned', 'ready'], true)
            || ! is_array($journal['media'] ?? null) || ! array_is_list($journal['media'])) {
            throw new CatalogImportPublicationException('Durable publication media journal is invalid.');
        }
        $items = $run->items()->orderBy('id')->get()->all();
        if (count($journal['media']) !== count($items) || $items === []) {
            throw new CatalogImportPublicationException(
                'Durable publication media journal has an incomplete item ownership set.'
            );
        }
        $entryKeys = [
            'byte_length',
            'created',
            'creation_identity',
            'database_path',
            'external_id',
            'item_id',
            'relative_path',
            'sha256',
            'status',
            'was_absent',
        ];
        foreach ($journal['media'] as $index => $entry) {
            $item = $items[$index];
            $keys = is_array($entry) ? array_keys($entry) : [];
            sort($keys, SORT_STRING);
            $relativePath = $this->images->destination($run, $item);
            if (! is_array($entry) || $keys !== $entryKeys
                || ($entry['item_id'] ?? null) !== $item->id
                || ($entry['external_id'] ?? null) !== $item->external_id
                || ($entry['relative_path'] ?? null) !== $relativePath
                || ($entry['database_path'] ?? null) !== 'storage/'.$relativePath
                || ($entry['sha256'] ?? null) !== $item->source_image_sha256
                || ($entry['byte_length'] ?? null) !== $item->source_image_byte_length
                || ! is_bool($entry['was_absent'] ?? null)
                || ! in_array($entry['status'] ?? null, ['planned', 'published'], true)
                || ($entry['status'] === 'planned' && $entry['created'] !== null)
                || ($entry['status'] === 'planned' && $entry['creation_identity'] !== null)
                || ($entry['status'] === 'published'
                    && (! is_bool($entry['created']) || $entry['created'] !== $entry['was_absent']))
                || ($entry['status'] === 'published' && $entry['created'] === true
                    && ! $this->validFileIdentity($entry['creation_identity'] ?? null))
                || ($entry['status'] === 'published' && $entry['created'] === false
                    && $entry['creation_identity'] !== null)) {
                throw new CatalogImportPublicationException(
                    'Durable publication media journal does not match reviewed item ownership.'
                );
            }
        }
        $allPublished = collect($journal['media'])
            ->every(static fn (array $entry): bool => $entry['status'] === 'published');
        if (($journal['status'] === 'ready') !== $allPublished) {
            throw new CatalogImportPublicationException('Durable publication media journal status is invalid.');
        }

        return [$journal, $journal['media'], $items];
    }

    /** @param array<string, mixed> $entry */
    private function imageFromJournal(array $entry): PublishedCatalogImportImage
    {
        return new PublishedCatalogImportImage(
            relativePath: $entry['relative_path'],
            databasePath: $entry['database_path'],
            sha256: $entry['sha256'],
            byteLength: $entry['byte_length'],
            created: $entry['created'],
            creationIdentity: $entry['creation_identity'],
        );
    }

    private function validFileIdentity(mixed $identity): bool
    {
        $keys = is_array($identity) ? array_keys($identity) : [];
        sort($keys, SORT_STRING);

        return is_array($identity) && $keys === ['dev', 'ino']
            && is_int($identity['dev']) && is_int($identity['ino']);
    }

    /** @param array<int, PublishedCatalogImportImage> $publishedImages */
    private function publishCatalog(
        CatalogImportRun $run,
        int $categoryId,
        int $baseSubcategoryId,
        array $publishedImages,
    ): void {
        $now = now();
        $subcategoryIds = [];
        foreach ($run->sources()->orderBy('sort_order')->get() as $source) {
            if ($source->publication_snapshot !== null || $source->published_subcategory_id !== null) {
                throw new CatalogImportPublicationException('Original source publication snapshot is immutable.');
            }
            $subcategoryId = DB::table('subcategories')->insertGetId([
                'category_id' => $categoryId,
                'title' => $source->rewritten_title,
                'titleh1' => $source->rewritten_h1,
                'first_screen_text' => $source->rewritten_intro,
                'description' => $source->rewritten_description,
                'seo' => $source->rewritten_seo,
                'slug' => $source->target_slug,
                'show_in_catalog' => true,
                'show_in_menu' => false,
                'is_import_collection' => true,
                'import_run_id' => $run->id,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $row = $this->state->row('subcategories', $subcategoryId);
            $source->update([
                'published_subcategory_id' => $subcategoryId,
                'created_subcategory' => true,
                'publication_snapshot' => [
                    'version' => 1,
                    'entity' => 'subcategory',
                    'entity_id' => $subcategoryId,
                    'created' => true,
                    'row' => $row,
                    'fingerprint' => $this->state->fingerprint($row),
                ],
            ]);
            $subcategoryIds[$source->id] = $subcategoryId;
        }

        foreach ($run->items()->orderBy('id')->get() as $item) {
            if ($item->publication_snapshot !== null || $item->published_product_id !== null) {
                throw new CatalogImportPublicationException('Original item publication snapshot is immutable.');
            }
            $publishedImage = $publishedImages[$item->id] ?? null;
            if (! $publishedImage instanceof PublishedCatalogImportImage) {
                throw new CatalogImportPublicationException('Verified public image is missing from publication attempt.');
            }
            $productId = DB::table('products')->insertGetId([
                'category_id' => $categoryId,
                'subcategory_id' => $baseSubcategoryId,
                'title' => $item->rewritten_title,
                'h1' => $item->rewritten_title,
                'first_screenn_description' => $item->rewritten_summary,
                'description' => $item->rewritten_description,
                'seo' => $item->rewritten_summary,
                'slug' => $item->rewritten_slug,
                'image_path' => $publishedImage->databasePath,
                'image_thumb_path' => null,
                'show_in_catalog' => true,
                'show_in_menu' => false,
                'source_provider' => $item->provider,
                'source_external_id' => $item->external_id,
                'source_url' => $item->source_url,
                'source_price' => $item->source_price,
                'import_run_id' => $run->id,
                'calculator_enabled' => false,
                'min_price' => null,
                'min_price_updated_at' => null,
                'min_price_error' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $sourceIds = DB::table('catalog_import_item_source')
                ->where('import_item_id', $item->id)
                ->orderBy('import_source_id')
                ->pluck('import_source_id')
                ->all();
            foreach ($sourceIds as $sourceId) {
                $subcategoryId = $subcategoryIds[$sourceId] ?? null;
                if ($subcategoryId === null) {
                    throw new CatalogImportPublicationException('Approved collection membership lost run ownership.');
                }
                DB::table('catalog_collection_product')->insert([
                    'subcategory_id' => $subcategoryId,
                    'product_id' => $productId,
                    'catalog_import_run_id' => $run->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $publicAttributeValueIds = DB::table('catalog_import_item_attribute_value as pivot')
                ->join('catalog_attribute_values as value', 'value.id', '=', 'pivot.attribute_value_id')
                ->join('catalog_attributes as attribute', 'attribute.id', '=', 'value.catalog_attribute_id')
                ->where('pivot.import_item_id', $item->id)
                ->where('attribute.is_public', true)
                ->orderBy('pivot.attribute_value_id')
                ->pluck('pivot.attribute_value_id')
                ->all();
            foreach ($publicAttributeValueIds as $attributeValueId) {
                DB::table('catalog_product_attribute_value')->insert([
                    'product_id' => $productId,
                    'attribute_value_id' => $attributeValueId,
                    'catalog_import_run_id' => $run->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $row = $this->state->row('products', $productId);
            $item->update([
                'review_status' => CatalogImportItem::STATUS_PUBLISHED,
                'published_product_id' => $productId,
                'created_product' => true,
                'publication_snapshot' => [
                    'version' => 1,
                    'entity' => 'product',
                    'entity_id' => $productId,
                    'created' => true,
                    'row' => $row,
                    'fingerprint' => $this->state->fingerprint($row),
                    'collection_pivots' => $this->state->pivots(
                        'catalog_collection_product',
                        'product_id',
                        $productId,
                    ),
                    'attribute_pivots' => $this->state->pivots(
                        'catalog_product_attribute_value',
                        'product_id',
                        $productId,
                    ),
                    'attribute_metadata' => $this->state->attributeMetadata(
                        array_map('intval', $publicAttributeValueIds),
                    ),
                    'media' => [$publishedImage->snapshot()],
                ],
            ]);
        }

        $run->update([
            'status' => CatalogImportRun::STATUS_PUBLISHED,
            'published_at' => $now,
            'publication_error' => null,
            'sitemap_generated_at' => null,
            'sitemap_error' => null,
        ]);
    }

    private function assertPublishedRunIntact(CatalogImportRun $run): void
    {
        if ($run->backup_created_at === null || $run->backup_path === null
            || $run->backup_manifest_path === null || $run->backup_manifest_sha256 === null
            || $run->backup_sha256 === null) {
            throw new CatalogImportPublicationException('Published run has no recorded verified backup.');
        }
        $sources = $run->sources()->orderBy('id')->get();
        if ($run->source_count !== 46 || $sources->count() !== 46) {
            throw new CatalogImportPublicationException('Published source ownership set is incomplete.');
        }
        $subcategoryIds = [];
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
            $subcategoryIds[] = (int) $snapshot['entity_id'];
        }
        $items = $run->items()->orderBy('id')->get();
        if ($run->unique_product_count < 1 || $items->count() !== $run->unique_product_count
            || $run->image_count !== $items->count()) {
            throw new CatalogImportPublicationException('Published product ownership set is incomplete.');
        }
        $productIds = [];
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
            $productId = (int) $snapshot['entity_id'];
            $productIds[] = $productId;
            $snapshotCollections = $snapshot['collection_pivots'] ?? null;
            $snapshotAttributes = $snapshot['attribute_pivots'] ?? null;
            if (! is_array($snapshotCollections) || ! is_array($snapshotAttributes)
                || ! $this->state->equivalent(
                    $this->state->pivots('catalog_collection_product', 'product_id', $productId),
                    $snapshotCollections,
                )
                || ! $this->state->equivalent(
                    $this->state->pivots('catalog_product_attribute_value', 'product_id', $productId),
                    $snapshotAttributes,
                )) {
                throw new CatalogImportPublicationException('Published catalog pivot ownership changed after publication.');
            }
            $attributeValueIds = array_map(
                static fn (array $pivot): int => (int) $pivot['attribute_value_id'],
                $snapshotAttributes,
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
            foreach ($mediaSnapshots as $media) {
                if (! is_array($media) || ($media['relative_path'] ?? null) !== $expectedMediaPath) {
                    throw new CatalogImportPublicationException('Published product media ownership is invalid.');
                }
                $this->images->assertOwnedPublishedSnapshot($media);
            }
        }
        sort($productIds, SORT_NUMERIC);
        sort($subcategoryIds, SORT_NUMERIC);
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
    }

    private function generateSitemap(CatalogImportRun $run, bool $noOp): CatalogImportPublicationResult
    {
        try {
            $this->sitemap->generate();
            $run->update([
                'sitemap_generated_at' => now(),
                'sitemap_error' => null,
            ]);

            return new CatalogImportPublicationResult(true, true, $noOp);
        } catch (Throwable $error) {
            $diagnostic = 'sitemap_generation_failed';
            $run->update([
                'sitemap_generated_at' => null,
                'sitemap_error' => $diagnostic,
            ]);

            return new CatalogImportPublicationResult(true, false, $noOp, $diagnostic);
        }
    }

    private function lockPublicationRows(CatalogImportRun $run): void
    {
        $sources = $run->sources()->orderBy('id')->lockForUpdate()->get();
        $items = $run->items()->orderBy('id')->lockForUpdate()->get();
        DB::table('categories')->where('slug', 'story')->lockForUpdate()->get();
        DB::table('subcategories')
            ->where('slug', 'rimskieshtory')
            ->orWhereIn('slug', $sources->pluck('target_slug')->all())
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
        DB::table('products')
            ->whereIn('slug', $items->pluck('rewritten_slug')->all())
            ->orWhere(function ($query) use ($items): void {
                foreach ($items as $item) {
                    $query->orWhere(function ($identity) use ($item): void {
                        $identity->where('source_provider', $item->provider)
                            ->where('source_external_id', $item->external_id);
                    });
                }
            })
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
        $itemIds = $items->pluck('id')->all();
        $attributeValueIds = DB::table('catalog_import_item_attribute_value')
            ->whereIn('import_item_id', $itemIds)
            ->orderBy('attribute_value_id')
            ->lockForUpdate()
            ->pluck('attribute_value_id')
            ->all();
        DB::table('catalog_attribute_values')
            ->whereIn('id', $attributeValueIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
        $attributeIds = DB::table('catalog_attribute_values')
            ->whereIn('id', $attributeValueIds)
            ->orderBy('catalog_attribute_id')
            ->pluck('catalog_attribute_id')
            ->all();
        DB::table('catalog_attributes')->whereIn('id', $attributeIds)->orderBy('id')->lockForUpdate()->get();
        DB::table('catalog_import_item_source')
            ->whereIn('import_item_id', $itemIds)
            ->orderBy('import_item_id')
            ->orderBy('import_source_id')
            ->lockForUpdate()
            ->get();
    }
}
