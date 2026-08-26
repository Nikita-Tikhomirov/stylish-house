<?php

namespace Tests\Feature;

use App\Data\CatalogImport\DatabaseBackupInvocation;
use App\Data\CatalogImport\DatabaseBackupRequest;
use App\Data\CatalogImport\QuarantinedCatalogImportImage;
use App\Data\CatalogImport\VerifiedDatabaseBackup;
use App\Models\CatalogAttribute;
use App\Models\CatalogImportRun;
use App\Services\CatalogImport\DatabaseBackup\DatabaseBackupException;
use App\Services\CatalogImport\DatabaseBackup\DatabaseBackupService;
use App\Services\CatalogImport\DatabaseBackup\DatabaseDumpRunner;
use App\Services\CatalogImport\DatabaseBackup\GzipBackupArchive;
use App\Services\CatalogImport\Publication\CatalogImportDatabaseBackupVerifier;
use App\Services\CatalogImport\Publication\CatalogImportImagePublisher;
use App\Services\CatalogImport\Publication\CatalogImportMutationLock;
use App\Services\CatalogImport\Publication\CatalogImportPublicationException;
use App\Services\CatalogImport\Publication\CatalogImportPublicationPreflight;
use App\Services\CatalogImport\Publication\CatalogImportPublisher;
use App\Services\CatalogImport\Publication\CatalogImportRollback;
use App\Services\CatalogImport\Publication\CatalogImportRollbackStateClassifier;
use App\Services\CatalogImport\Publication\CatalogImportSitemapGenerator;
use App\Services\CatalogImport\Publication\LaravelCatalogImportTransaction;
use DateTimeImmutable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CatalogImportPublicationTestCase;

class CatalogImportRollbackTest extends CatalogImportPublicationTestCase
{
    private string $backupDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->backupDirectory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'rollback-backup-'.bin2hex(random_bytes(8));
        mkdir($this->backupDirectory, 0700, true);
        config()->set('catalog-import-publication.enabled', true);
        config()->set('catalog-import-backup.destination', $this->backupDirectory);
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->backupDirectory);

        parent::tearDown();
    }

    public function test_rollback_deletes_only_run_created_catalog_rows_pivots_and_media(): void
    {
        $this->seedCatalogRoots();
        $run = $this->publishReviewedRun();
        $legacyProductId = DB::table('products')->insertGetId([
            'title' => 'Legacy',
            'slug' => 'legacy',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $backupHash = hash_file('sha256', $run->backup_path);

        $result = $this->rollback()->rollback($run);

        $this->assertTrue($result->rolledBack);
        $this->assertFalse($result->noOp);
        $this->assertSame(CatalogImportRun::STATUS_ROLLED_BACK, $run->fresh()->status);
        $this->assertNotNull($run->fresh()->rolled_back_at);
        $this->assertSame([$legacyProductId], DB::table('products')->pluck('id')->all());
        $this->assertSame(0, DB::table('subcategories')->where('is_import_collection', true)->count());
        $this->assertSame(0, DB::table('catalog_collection_product')->count());
        $this->assertSame(0, DB::table('catalog_product_attribute_value')->count());
        Storage::disk('public')->assertMissing('catalog-imports/full-run-001/images/11889.webp');
        $this->assertSame([], Storage::disk('local')->allFiles('catalog-import-rollbacks'));
        $this->assertSame($backupHash, hash_file('sha256', $run->backup_path));
        $this->assertFileExists($run->fresh()->rollback_backup_path);
        $this->assertNotSame($run->backup_path, $run->fresh()->rollback_backup_path);
        $this->assertNull($run->items()->firstOrFail()->published_product_id);
        $this->assertNull($run->sources()->firstOrFail()->published_subcategory_id);
        $this->assertNotNull($run->items()->firstOrFail()->publication_snapshot);
    }

    public function test_matching_preexisting_public_media_survives_publish_retry_and_rollback(): void
    {
        $this->seedCatalogRoots();
        $run = $this->seedReviewedRun();
        $path = 'catalog-imports/full-run-001/images/11889.webp';
        Storage::disk('public')->put($path, $this->validWebp);
        $images = new CatalogImportImagePublisher;
        $publisher = new CatalogImportPublisher(
            preflight: new CatalogImportPublicationPreflight($images),
            backup: new RollbackTestBackupService($this->backupDirectory),
            images: $images,
            lock: new CatalogImportMutationLock,
            sitemap: new RollbackTestSitemapGenerator,
        );

        $publisher->publish($run);
        $snapshot = $run->items()->firstOrFail()->publication_snapshot['media'][0];
        $this->assertFalse($snapshot['created']);
        $this->assertNull($snapshot['creation_identity']);
        $this->assertTrue($publisher->publish($run->fresh())->noOp);

        $result = $this->rollback()->rollback($run->fresh());

        $this->assertTrue($result->rolledBack);
        Storage::disk('public')->assertExists($path);
        $this->assertSame($this->validWebp, Storage::disk('public')->get($path));
    }

    public function test_fresh_verified_rollback_backup_precedes_journal_and_catalog_mutation(): void
    {
        $this->seedCatalogRoots();
        $run = $this->publishReviewedRun();
        $backup = new RollbackTestBackupService(
            $this->backupDirectory,
            beforeCreate: function (DatabaseBackupRequest $request) use ($run): void {
                $current = $run->fresh();
                $this->assertSame('full-run-001', $request->runId);
                $this->assertNull($current->rollback_backup_created_at);
                $this->assertNull($current->rollback_journal);
                $this->assertSame(1, DB::table('products')->where('import_run_id', $run->id)->count());
                Storage::disk('public')->assertExists('catalog-imports/full-run-001/images/11889.webp');
            },
        );

        $this->rollback(backup: $backup)->rollback($run);

        $this->assertSame(1, $backup->calls);
        $this->assertNotNull($run->fresh()->rollback_backup_created_at);
        $this->assertMatchesRegularExpression(
            '/^[a-f0-9]{64}$/D',
            $run->fresh()->rollback_backup_manifest_sha256,
        );
    }

    public function test_rollback_backup_preserves_maximum_length_external_run_id_verbatim(): void
    {
        $this->seedCatalogRoots();
        $externalRunId = str_repeat('a', 80);
        $run = $this->seedReviewedRun(['external_run_id' => $externalRunId]);
        $item = $run->items()->firstOrFail();
        $privatePath = 'catalog-imports/'.$externalRunId.'/images/11889.webp';
        Storage::disk('local')->put($privatePath, $this->validWebp);
        $item->update(['source_image_path' => $privatePath]);
        $images = new CatalogImportImagePublisher;
        (new CatalogImportPublisher(
            preflight: new CatalogImportPublicationPreflight($images),
            backup: new RollbackTestBackupService($this->backupDirectory),
            images: $images,
            lock: new CatalogImportMutationLock,
            sitemap: new RollbackTestSitemapGenerator,
        ))->publish($run);
        $run = $run->fresh();
        $backup = new RollbackTestBackupService(
            $this->backupDirectory,
            beforeCreate: function (DatabaseBackupRequest $request) use ($externalRunId): void {
                $this->assertSame($externalRunId, $request->runId);
                $this->assertSame(80, strlen($request->runId));
            },
        );

        $this->rollback(backup: $backup)->rollback($run);

        $this->assertSame(CatalogImportRun::STATUS_ROLLED_BACK, $run->fresh()->status);
        $this->assertSame(1, $backup->calls);
    }

    public function test_rollback_backup_failure_leaves_catalog_journal_and_media_untouched(): void
    {
        $this->seedCatalogRoots();
        $run = $this->publishReviewedRun();
        $backup = new RollbackTestBackupService($this->backupDirectory, fail: true);

        try {
            $this->rollback(backup: $backup)->rollback($run);
            $this->fail('A rollback backup failure must abort before mutation.');
        } catch (DatabaseBackupException) {
            // Expected: backup service failed before any run/catalog/media write.
        }

        $failed = $run->fresh();
        $this->assertNull($failed->rollback_backup_created_at);
        $this->assertNull($failed->rollback_backup_path);
        $this->assertNull($failed->rollback_journal);
        $this->assertNull($failed->rollback_error);
        $this->assertSame(CatalogImportRun::STATUS_PUBLISHED, $failed->status);
        $this->assertSame(1, DB::table('products')->where('import_run_id', $run->id)->count());
        Storage::disk('public')->assertExists('catalog-imports/full-run-001/images/11889.webp');
        $this->assertSame([], Storage::disk('local')->allFiles('catalog-import-rollbacks'));
    }

    public function test_tampered_recorded_rollback_backup_aborts_without_new_dump_or_mutation(): void
    {
        $this->seedCatalogRoots();
        $run = $this->publishReviewedRun();
        $this->recordRollbackBackup($run);
        file_put_contents($run->fresh()->rollback_backup_path, 'tampered rollback archive');
        $backup = new RollbackTestBackupService($this->backupDirectory);

        $this->expectException(CatalogImportPublicationException::class);
        $this->expectExceptionMessage('rollback database backup');
        try {
            $this->rollback(backup: $backup)->rollback($run->fresh());
        } finally {
            $this->assertSame(0, $backup->calls);
            $this->assertNull($run->fresh()->rollback_journal);
            $this->assertSame(CatalogImportRun::STATUS_PUBLISHED, $run->fresh()->status);
            $this->assertSame(1, DB::table('products')->where('import_run_id', $run->id)->count());
            Storage::disk('public')->assertExists('catalog-imports/full-run-001/images/11889.webp');
        }
    }

    public function test_second_rollback_is_a_noop(): void
    {
        $this->seedCatalogRoots();
        $run = $this->publishReviewedRun();
        $rollback = $this->rollback();
        $rollback->rollback($run);

        $second = $rollback->rollback($run->fresh());

        $this->assertTrue($second->rolledBack);
        $this->assertTrue($second->noOp);
    }

    public function test_manual_product_change_aborts_entire_rollback_and_preserves_media(): void
    {
        $this->seedCatalogRoots();
        $run = $this->publishReviewedRun();
        DB::table('products')->where('import_run_id', $run->id)->update(['title' => 'Manual edit']);

        $this->expectException(CatalogImportPublicationException::class);
        try {
            $this->rollback()->rollback($run);
        } finally {
            $this->assertSame(CatalogImportRun::STATUS_PUBLISHED, $run->fresh()->status);
            $this->assertSame(1, DB::table('products')->where('import_run_id', $run->id)->count());
            Storage::disk('public')->assertExists('catalog-imports/full-run-001/images/11889.webp');
        }
    }

    public function test_foreign_product_reference_to_owned_subcategory_aborts_before_backup_or_mutation(): void
    {
        $this->seedCatalogRoots();
        $run = $this->publishReviewedRun();
        $ownedSubcategoryId = (int) $run->sources()->firstOrFail()->published_subcategory_id;
        $foreignProductId = DB::table('products')->insertGetId([
            'subcategory_id' => $ownedSubcategoryId,
            'title' => 'Foreign product',
            'slug' => 'foreign-product',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $backup = new RollbackTestBackupService($this->backupDirectory);

        try {
            $this->rollback(backup: $backup)->rollback($run);
            $this->fail('Rollback must not delete a collection referenced by an unowned product.');
        } catch (CatalogImportPublicationException $exception) {
            $this->assertStringContainsString('referenced outside', $exception->getMessage());
        }

        $this->assertSame(0, $backup->calls);
        $this->assertSame(CatalogImportRun::STATUS_PUBLISHED, $run->fresh()->status);
        $this->assertSame(
            $ownedSubcategoryId,
            (int) DB::table('products')->where('id', $foreignProductId)->value('subcategory_id'),
        );
        $this->assertTrue(DB::table('subcategories')->where('id', $ownedSubcategoryId)->exists());
        Storage::disk('public')->assertExists('catalog-imports/full-run-001/images/11889.webp');
    }

    public function test_faq_reference_to_owned_subcategory_aborts_before_cascade_delete(): void
    {
        $this->seedCatalogRoots();
        $run = $this->publishReviewedRun();
        $ownedSubcategoryId = (int) $run->sources()->firstOrFail()->published_subcategory_id;
        Schema::create('faqstable', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('subcategory_id')->nullable();
            $table->string('question');
        });
        DB::table('faqstable')->insert([
            'subcategory_id' => $ownedSubcategoryId,
            'question' => 'User maintained FAQ',
        ]);
        $backup = new RollbackTestBackupService($this->backupDirectory);

        try {
            $this->rollback(backup: $backup)->rollback($run);
            $this->fail('Rollback must not cascade-delete a FAQ outside import ownership.');
        } catch (CatalogImportPublicationException $exception) {
            $this->assertStringContainsString('referenced outside', $exception->getMessage());
        } finally {
            $this->assertSame(0, $backup->calls);
            $this->assertSame(1, DB::table('faqstable')->count());
            $this->assertTrue(DB::table('subcategories')->where('id', $ownedSubcategoryId)->exists());
            Schema::dropIfExists('faqstable');
        }
    }

    #[DataProvider('externalProductReferenceTables')]
    public function test_external_product_reference_aborts_before_owned_product_delete(string $tableName): void
    {
        $this->seedCatalogRoots();
        $run = $this->publishReviewedRun();
        $ownedProductId = (int) $run->items()->firstOrFail()->published_product_id;
        Schema::create($tableName, function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('product_id');
        });
        DB::table($tableName)->insert(['product_id' => $ownedProductId]);
        $backup = new RollbackTestBackupService($this->backupDirectory);

        try {
            $this->rollback(backup: $backup)->rollback($run);
            $this->fail('Rollback must not delete a product referenced outside import ownership.');
        } catch (CatalogImportPublicationException $exception) {
            $this->assertStringContainsString('product is referenced outside', $exception->getMessage());
        } finally {
            $this->assertSame(0, $backup->calls);
            $this->assertSame(1, DB::table($tableName)->count());
            $this->assertTrue(DB::table('products')->where('id', $ownedProductId)->exists());
            Schema::dropIfExists($tableName);
        }
    }

    /** @return array<string, array{string}> */
    public static function externalProductReferenceTables(): array
    {
        return [
            'favorite' => ['favorites'],
            'tab' => ['tabs'],
            'first-screen slider' => ['first_screen_sliders'],
            'price recalculation history' => ['price_recalc_run_items'],
        ];
    }

    public function test_cross_run_attribute_pivot_aborts_rollback(): void
    {
        $this->seedCatalogRoots();
        $run = $this->publishReviewedRun();
        $otherRun = CatalogImportRun::create([
            'provider' => 'rimskie.com',
            'external_run_id' => 'other-run',
            'status' => CatalogImportRun::STATUS_STAGED,
        ]);
        $attribute = CatalogAttribute::create([
            'code' => 'material',
            'label' => 'Материал',
            'type' => CatalogAttribute::TYPE_SELECT,
            'sort_order' => 2,
            'is_public' => true,
        ]);
        $value = $attribute->values()->create([
            'normalized_value' => 'linen',
            'label' => 'Лён',
            'sort_order' => 1,
        ]);
        DB::table('catalog_product_attribute_value')->insert([
            'product_id' => DB::table('products')->where('import_run_id', $run->id)->value('id'),
            'attribute_value_id' => $value->id,
            'catalog_import_run_id' => $otherRun->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(CatalogImportPublicationException::class);
        $this->expectExceptionMessage('pivot ownership');

        $this->rollback()->rollback($run);
    }

    public function test_changed_public_media_aborts_before_database_mutation(): void
    {
        $this->seedCatalogRoots();
        $run = $this->publishReviewedRun();
        Storage::disk('public')->put('catalog-imports/full-run-001/images/11889.webp', 'changed');

        $this->expectException(CatalogImportPublicationException::class);
        try {
            $this->rollback()->rollback($run);
        } finally {
            $this->assertSame(1, DB::table('products')->where('import_run_id', $run->id)->count());
            $this->assertSame('changed', Storage::disk('public')->get('catalog-imports/full-run-001/images/11889.webp'));
        }
    }

    public function test_same_bytes_public_media_replacement_aborts_before_rollback_backup(): void
    {
        $this->seedCatalogRoots();
        $run = $this->publishReviewedRun();
        $path = 'catalog-imports/full-run-001/images/11889.webp';
        unlink(Storage::disk('public')->path($path));
        Storage::disk('public')->put($path, $this->validWebp);
        $backup = new RollbackTestBackupService($this->backupDirectory);

        try {
            $this->rollback(backup: $backup)->rollback($run);
            $this->fail('A same-bytes public replacement must not be deleted as run-created media.');
        } catch (CatalogImportPublicationException $exception) {
            $this->assertStringContainsString('identity', $exception->getMessage());
        }

        $this->assertSame(0, $backup->calls);
        $this->assertSame(CatalogImportRun::STATUS_PUBLISHED, $run->fresh()->status);
        Storage::disk('public')->assertExists($path);
        $this->assertSame($this->validWebp, Storage::disk('public')->get($path));
    }

    public function test_missing_media_snapshot_aborts_rollback_before_backup_or_mutation(): void
    {
        $this->seedCatalogRoots();
        $run = $this->publishReviewedRun();
        $item = $run->items()->firstOrFail();
        $snapshot = $item->publication_snapshot;
        $snapshot['media'] = [];
        $item->update(['publication_snapshot' => $snapshot]);
        $backup = new RollbackTestBackupService($this->backupDirectory);

        $this->expectException(CatalogImportPublicationException::class);
        $this->expectExceptionMessage('media ownership');
        try {
            $this->rollback(backup: $backup)->rollback($run->fresh());
        } finally {
            $this->assertSame(0, $backup->calls);
            $this->assertSame(CatalogImportRun::STATUS_PUBLISHED, $run->fresh()->status);
            $this->assertSame(1, DB::table('products')->where('import_run_id', $run->id)->count());
            Storage::disk('public')->assertExists('catalog-imports/full-run-001/images/11889.webp');
        }
    }

    public function test_database_failure_restores_quarantined_media_and_persists_safe_error(): void
    {
        $this->seedCatalogRoots();
        $run = $this->publishReviewedRun();
        $secret = 'TOP_SECRET_ROLLBACK_TRIGGER';
        DB::statement(
            'CREATE TRIGGER reject_import_delete BEFORE DELETE ON products '
            ."BEGIN SELECT RAISE(ABORT, '$secret'); END"
        );

        try {
            $this->rollback()->rollback($run);
            $this->fail('Controlled rollback delete must fail.');
        } catch (CatalogImportPublicationException $exception) {
            $this->assertStringNotContainsString($secret, $exception->getMessage());
        }

        $this->assertSame(CatalogImportRun::STATUS_PUBLISHED, $run->fresh()->status);
        $this->assertSame('rollback_failed', $run->fresh()->rollback_error);
        $this->assertSame(1, DB::table('products')->where('import_run_id', $run->id)->count());
        $this->assertSame($this->validWebp, Storage::disk('public')->get('catalog-imports/full-run-001/images/11889.webp'));
        $this->assertSame([], Storage::disk('local')->allFiles('catalog-import-rollbacks'));
    }

    public function test_retry_after_restored_failed_attempt_creates_a_fresh_rollback_backup(): void
    {
        $this->seedCatalogRoots();
        $run = $this->publishReviewedRun();
        DB::statement(
            'CREATE TRIGGER reject_first_import_delete BEFORE DELETE ON products '
            .'BEGIN SELECT RAISE(ABORT, "first rollback fails"); END'
        );
        $backup = new RollbackTestBackupService($this->backupDirectory);
        try {
            $this->rollback(backup: $backup)->rollback($run);
            $this->fail('The first rollback attempt must fail before commit.');
        } catch (CatalogImportPublicationException) {
            // The published catalog and public media were restored.
        }
        DB::statement('DROP TRIGGER reject_first_import_delete');
        DB::table('products')->insert([
            'title' => 'Unrelated post-failure change',
            'slug' => 'unrelated-post-failure-change',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $result = $this->rollback(backup: $backup)->rollback($run->fresh());

        $this->assertTrue($result->rolledBack);
        $this->assertSame(2, $backup->calls);
        $this->assertSame(CatalogImportRun::STATUS_ROLLED_BACK, $run->fresh()->status);
    }

    public function test_public_attribute_metadata_change_aborts_rollback(): void
    {
        $this->seedCatalogRoots();
        $run = $this->publishReviewedRun();
        DB::table('catalog_attribute_values')->where('normalized_value', 'white')->update([
            'label' => 'Изменено вручную',
        ]);

        $this->expectException(CatalogImportPublicationException::class);
        $this->expectExceptionMessage('attribute metadata');

        $this->rollback()->rollback($run);
    }

    public function test_persisted_quarantine_journal_recovers_after_process_restart(): void
    {
        $this->seedCatalogRoots();
        $run = $this->publishReviewedRun();
        $images = new CatalogImportImagePublisher;
        $media = $run->items()->firstOrFail()->publication_snapshot['media'];
        $plan = $images->planQuarantine($run, $media);
        $this->recordRollbackBackup($run);
        $run->update([
            'rollback_journal' => [
                'version' => 1,
                'status' => 'planned',
                'media' => array_map(static fn ($image): array => $image->snapshot(), $plan),
            ],
        ]);
        $images->quarantinePlanned($plan);
        Storage::disk('public')->assertMissing('catalog-imports/full-run-001/images/11889.webp');
        $this->assertNotSame([], Storage::disk('local')->allFiles('catalog-import-rollbacks'));

        $result = $this->rollback()->rollback($run->fresh());

        $this->assertTrue($result->rolledBack);
        $this->assertSame(CatalogImportRun::STATUS_ROLLED_BACK, $run->fresh()->status);
        $this->assertNull($run->fresh()->rollback_journal);
        $this->assertSame([], Storage::disk('local')->allFiles('catalog-import-rollbacks'));
        Storage::disk('public')->assertMissing('catalog-imports/full-run-001/images/11889.webp');
    }

    public function test_rollback_rejects_changed_created_ownership_flags_before_backup(): void
    {
        $this->seedCatalogRoots();
        $run = $this->publishReviewedRun();
        $run->sources()->firstOrFail()->update(['created_subcategory' => false]);
        $backup = new RollbackTestBackupService($this->backupDirectory);

        try {
            $this->rollback(backup: $backup)->rollback($run->fresh());
            $this->fail('Rollback must require the explicit run-created ownership flag.');
        } catch (CatalogImportPublicationException $exception) {
            $this->assertStringContainsString('ownership snapshot', $exception->getMessage());
        }

        $this->assertSame(0, $backup->calls);
        $this->assertSame(CatalogImportRun::STATUS_PUBLISHED, $run->fresh()->status);
        Storage::disk('public')->assertExists('catalog-imports/full-run-001/images/11889.webp');
    }

    public function test_rollback_rejects_coordinated_missing_staging_item_and_owned_rows_before_backup(): void
    {
        $this->seedCatalogRoots();
        $run = $this->publishReviewedRun();
        $productId = $run->items()->firstOrFail()->published_product_id;
        DB::table('catalog_collection_product')->where('product_id', $productId)->delete();
        DB::table('catalog_product_attribute_value')->where('product_id', $productId)->delete();
        DB::table('products')->where('id', $productId)->delete();
        $run->items()->firstOrFail()->delete();
        $backup = new RollbackTestBackupService($this->backupDirectory);

        try {
            $this->rollback(backup: $backup)->rollback($run->fresh());
            $this->fail('Rollback must require the immutable exact staging item count.');
        } catch (CatalogImportPublicationException $exception) {
            $this->assertStringContainsString('ownership set', $exception->getMessage());
        }

        $this->assertSame(0, $backup->calls);
        $this->assertSame(CatalogImportRun::STATUS_PUBLISHED, $run->fresh()->status);
        Storage::disk('public')->assertExists('catalog-imports/full-run-001/images/11889.webp');
    }

    public function test_rollback_accepts_mysql_style_reordered_journal_object_keys(): void
    {
        $this->seedCatalogRoots();
        $run = $this->publishReviewedRun();
        $images = new CatalogImportImagePublisher;
        $media = $run->items()->firstOrFail()->publication_snapshot['media'];
        $plan = $images->planQuarantine($run, $media);
        $this->recordRollbackBackup($run);
        $entries = array_map(
            static function ($image): array {
                $entry = $image->snapshot();
                $entry['file_identity'] = array_reverse($entry['file_identity'], true);

                return array_reverse($entry, true);
            },
            $plan,
        );
        $run->update([
            'rollback_journal' => [
                'media' => $entries,
                'status' => 'planned',
                'version' => 1,
            ],
        ]);
        $images->quarantinePlanned($plan);

        try {
            $result = $this->rollback()->rollback($run->fresh());
        } catch (CatalogImportPublicationException $exception) {
            $this->fail('Associative journal key order must not invalidate recovery: '.$exception->getMessage());
        }

        $this->assertTrue($result->rolledBack);
        $this->assertSame(CatalogImportRun::STATUS_ROLLED_BACK, $run->fresh()->status);
        $this->assertNull($run->fresh()->rollback_journal);
    }

    public function test_commit_success_followed_by_transport_exception_does_not_restore_orphan_media(): void
    {
        $this->seedCatalogRoots();
        $run = $this->publishReviewedRun();
        $rollback = $this->rollback(new CommitThenThrowCatalogImportTransaction);

        $result = $rollback->rollback($run);

        $this->assertTrue($result->rolledBack);
        $this->assertSame(CatalogImportRun::STATUS_ROLLED_BACK, $run->fresh()->status);
        $this->assertSame(0, DB::table('products')->where('import_run_id', $run->id)->count());
        Storage::disk('public')->assertMissing('catalog-imports/full-run-001/images/11889.webp');
        $this->assertSame([], Storage::disk('local')->allFiles('catalog-import-rollbacks'));
        $this->assertNull($run->fresh()->rollback_journal);
    }

    public function test_uncertain_commit_confirmation_preserves_trash_and_durable_journal(): void
    {
        $this->seedCatalogRoots();
        $run = $this->publishReviewedRun();
        $rollback = $this->rollback(
            new ThrowBeforeCommitCatalogImportTransaction,
            new FailingCatalogImportRollbackStateClassifier,
        );

        try {
            $rollback->rollback($run);
            $this->fail('Uncertain rollback state must require manual verification.');
        } catch (CatalogImportPublicationException $exception) {
            $this->assertStringContainsString('manual verification', $exception->getMessage());
        }

        $this->assertSame(CatalogImportRun::STATUS_PUBLISHED, $run->fresh()->status);
        $this->assertSame('rollback_state_uncertain', $run->fresh()->rollback_error);
        $this->assertNotNull($run->fresh()->rollback_journal);
        Storage::disk('public')->assertMissing('catalog-imports/full-run-001/images/11889.webp');
        $this->assertNotSame([], Storage::disk('local')->allFiles('catalog-import-rollbacks'));
    }

    public function test_rolled_back_retry_purges_pending_durable_trash_after_crash(): void
    {
        $this->seedCatalogRoots();
        $run = $this->publishReviewedRun();
        $images = new CatalogImportImagePublisher;
        $item = $run->items()->firstOrFail();
        $plan = $images->planQuarantine($run, $item->publication_snapshot['media']);
        $this->recordRollbackBackup($run);
        $run->update([
            'rollback_journal' => [
                'version' => 1,
                'status' => 'planned',
                'media' => array_map(static fn ($image): array => $image->snapshot(), $plan),
            ],
        ]);
        $images->quarantinePlanned($plan);
        $productIds = $run->items()->pluck('published_product_id')->all();
        $subcategoryIds = $run->sources()->pluck('published_subcategory_id')->all();
        DB::transaction(function () use ($run, $productIds, $subcategoryIds): void {
            DB::table('catalog_collection_product')->whereIn('product_id', $productIds)->delete();
            DB::table('catalog_product_attribute_value')->whereIn('product_id', $productIds)->delete();
            DB::table('products')->whereIn('id', $productIds)->delete();
            DB::table('subcategories')->whereIn('id', $subcategoryIds)->delete();
            $run->update([
                'status' => CatalogImportRun::STATUS_ROLLED_BACK,
                'rolled_back_at' => now(),
            ]);
        });
        $this->assertNotSame([], Storage::disk('local')->allFiles('catalog-import-rollbacks'));

        $result = $this->rollback()->rollback($run->fresh());

        $this->assertTrue($result->rolledBack);
        $this->assertTrue($result->noOp);
        $this->assertNull($run->fresh()->rollback_journal);
        $this->assertSame([], Storage::disk('local')->allFiles('catalog-import-rollbacks'));
    }

    public function test_rolled_back_cleanup_rejects_changed_created_flag_and_preserves_trash(): void
    {
        $this->seedCatalogRoots();
        $run = $this->publishReviewedRun();
        $images = new CatalogImportImagePublisher;
        $item = $run->items()->firstOrFail();
        $plan = $images->planQuarantine($run, $item->publication_snapshot['media']);
        $this->recordRollbackBackup($run);
        $run->update([
            'rollback_journal' => [
                'version' => 1,
                'status' => 'planned',
                'media' => array_map(static fn ($image): array => $image->snapshot(), $plan),
            ],
        ]);
        $images->quarantinePlanned($plan);
        $productIds = $run->items()->pluck('published_product_id')->all();
        $subcategoryIds = $run->sources()->pluck('published_subcategory_id')->all();
        DB::transaction(function () use ($run, $productIds, $subcategoryIds): void {
            DB::table('catalog_collection_product')->whereIn('product_id', $productIds)->delete();
            DB::table('catalog_product_attribute_value')->whereIn('product_id', $productIds)->delete();
            DB::table('products')->whereIn('id', $productIds)->delete();
            DB::table('subcategories')->whereIn('id', $subcategoryIds)->delete();
            $run->update([
                'status' => CatalogImportRun::STATUS_ROLLED_BACK,
                'rolled_back_at' => now(),
            ]);
        });
        $run->items()->firstOrFail()->update(['created_product' => false]);

        try {
            $this->rollback()->rollback($run->fresh());
            $this->fail('Committed rollback cleanup must retain explicit created ownership.');
        } catch (CatalogImportPublicationException $exception) {
            $this->assertStringContainsString('ownership snapshot', $exception->getMessage());
        }

        $this->assertNotSame([], Storage::disk('local')->allFiles('catalog-import-rollbacks'));
        $this->assertNotNull($run->fresh()->rollback_journal);
    }

    public function test_post_move_verification_failure_preserves_durable_journal_and_evidence(): void
    {
        $this->seedCatalogRoots();
        $run = $this->publishReviewedRun();
        $images = new CatalogImportImagePublisher(
            afterQuarantineLink: static function (QuarantinedCatalogImportImage $image): void {
                $path = Storage::disk('local')->path($image->trashRelativePath);
                unlink($path);
                file_put_contents($path, 'changed-after-quarantine-link');
            },
        );

        try {
            $this->rollback(images: $images)->rollback($run);
            $this->fail('A post-move verification failure must require manual recovery.');
        } catch (CatalogImportPublicationException $exception) {
            $this->assertStringContainsString('manual verification', $exception->getMessage());
        }

        $failed = $run->fresh();
        $this->assertSame(CatalogImportRun::STATUS_PUBLISHED, $failed->status);
        $this->assertSame('rollback_media_restore_failed', $failed->rollback_error);
        $this->assertNotNull($failed->rollback_journal);
        Storage::disk('public')->assertExists('catalog-imports/full-run-001/images/11889.webp');
        $this->assertSame($this->validWebp, Storage::disk('public')->get('catalog-imports/full-run-001/images/11889.webp'));
        $this->assertNotSame([], Storage::disk('local')->allFiles('catalog-import-rollbacks'));
    }

    private function publishReviewedRun(array $runOverrides = []): CatalogImportRun
    {
        $run = $this->seedReviewedRun($runOverrides);
        $images = new CatalogImportImagePublisher;
        $publisher = new CatalogImportPublisher(
            preflight: new CatalogImportPublicationPreflight($images),
            backup: new RollbackTestBackupService($this->backupDirectory),
            images: $images,
            lock: new CatalogImportMutationLock,
            sitemap: new RollbackTestSitemapGenerator,
        );
        $publisher->publish($run);

        return $run->fresh();
    }

    private function rollback(
        ?LaravelCatalogImportTransaction $transaction = null,
        ?CatalogImportRollbackStateClassifier $stateClassifier = null,
        ?CatalogImportImagePublisher $images = null,
        ?DatabaseBackupService $backup = null,
    ): CatalogImportRollback {
        return new CatalogImportRollback(
            backup: $backup ?? new RollbackTestBackupService($this->backupDirectory),
            images: $images ?? new CatalogImportImagePublisher,
            lock: new CatalogImportMutationLock,
            sitemap: new RollbackTestSitemapGenerator,
            transaction: $transaction,
            stateClassifier: $stateClassifier,
        );
    }

    private function recordRollbackBackup(CatalogImportRun $run): void
    {
        $backup = (new RollbackTestBackupService($this->backupDirectory))->create(
            new DatabaseBackupRequest(
                runId: $run->external_run_id,
                provider: $run->provider,
                connectionName: (string) config('database.default'),
                connection: (array) config('database.connections.'.config('database.default'), []),
            ),
        );
        $manifestSha256 = (new CatalogImportDatabaseBackupVerifier)->verifyCreated(
            $backup,
            $run->external_run_id,
            $run->provider,
        );
        $run->update([
            'rollback_backup_created_at' => $backup->verifiedAt,
            'rollback_backup_path' => $backup->archivePath,
            'rollback_backup_sha256' => $backup->gzipSha256,
            'rollback_backup_manifest_path' => $backup->manifestPath,
            'rollback_backup_manifest_sha256' => $manifestSha256,
            'rollback_backup_raw_sha256' => $backup->rawSha256,
            'rollback_backup_raw_size' => $backup->rawSize,
            'rollback_backup_gzip_size' => $backup->gzipSize,
        ]);
    }
}

class RollbackTestBackupService extends DatabaseBackupService
{
    public int $calls = 0;

    public function __construct(
        private readonly string $directory,
        private readonly ?\Closure $beforeCreate = null,
        private readonly bool $fail = false,
    ) {
        parent::__construct(
            runner: new class implements DatabaseDumpRunner
            {
                public function run(DatabaseBackupInvocation $invocation): void
                {
                    throw new \LogicException('Unused overridden backup runner.');
                }
            },
            archive: new GzipBackupArchive,
            destination: $directory,
            publicRoots: [sys_get_temp_dir().DIRECTORY_SEPARATOR.'unrelated-public-root'],
        );
    }

    public function create(DatabaseBackupRequest $request): VerifiedDatabaseBackup
    {
        $this->calls++;
        if ($this->beforeCreate !== null) {
            ($this->beforeCreate)($request);
        }
        if ($this->fail) {
            throw new DatabaseBackupException('Controlled rollback backup failure.');
        }
        $sql = "CREATE TABLE `products` (`id` bigint);\n";
        $baseName = preg_replace('/[^a-z0-9._-]+/i', '-', $request->runId)
            .'-'.$this->calls.'-'.bin2hex(random_bytes(4));
        $archivePath = $this->directory.DIRECTORY_SEPARATOR.$baseName.'.sql.gz';
        $stream = gzopen($archivePath, 'wb6');
        gzwrite($stream, $sql);
        gzclose($stream);
        if (PHP_OS_FAMILY !== 'Windows') {
            chmod($archivePath, 0600);
        }
        $rawSha256 = hash('sha256', $sql);
        $rawSize = strlen($sql);
        $gzipSha256 = hash_file('sha256', $archivePath);
        $gzipSize = filesize($archivePath);
        $manifest = [
            'schema' => 'catalog-import-database-backup',
            'version' => 1,
            'run' => ['id' => $request->runId, 'provider' => $request->provider],
            'timestamp_utc' => '2026-08-26T10:15:30.123456Z',
            'driver' => (string) ($request->connection['driver'] ?? ''),
            'connection' => [
                'name' => $request->connectionName,
                'host' => (string) ($request->connection['host'] ?? ''),
                'port' => (int) ($request->connection['port'] ?? 0),
                'database' => (string) ($request->connection['database'] ?? ''),
            ],
            'raw' => ['sha256' => $rawSha256, 'size' => $rawSize],
            'gzip' => ['sha256' => $gzipSha256, 'size' => $gzipSize],
            'verified_at' => '2026-08-26T10:15:30.123456Z',
        ];
        $manifestPath = $this->directory.DIRECTORY_SEPARATOR.$baseName.'.json';
        file_put_contents($manifestPath, json_encode($manifest, JSON_THROW_ON_ERROR));
        if (PHP_OS_FAMILY !== 'Windows') {
            chmod($manifestPath, 0600);
        }

        return new VerifiedDatabaseBackup(
            $archivePath,
            $manifestPath,
            $rawSha256,
            $rawSize,
            $gzipSha256,
            $gzipSize,
            new DateTimeImmutable('2026-08-26T10:15:30.123456Z'),
            $manifest,
        );
    }
}

class RollbackTestSitemapGenerator implements CatalogImportSitemapGenerator
{
    public function generate(): void {}
}

class CommitThenThrowCatalogImportTransaction extends LaravelCatalogImportTransaction
{
    public function commit(): void
    {
        parent::commit();

        throw new \RuntimeException('Connection lost after COMMIT.');
    }
}

class ThrowBeforeCommitCatalogImportTransaction extends LaravelCatalogImportTransaction
{
    public function commit(): void
    {
        throw new \RuntimeException('Connection lost before COMMIT acknowledgement.');
    }
}

class FailingCatalogImportRollbackStateClassifier extends CatalogImportRollbackStateClassifier
{
    public function classify(CatalogImportRun $run, array $productIds, array $subcategoryIds): string
    {
        throw new \RuntimeException('Connection state unavailable.');
    }
}
