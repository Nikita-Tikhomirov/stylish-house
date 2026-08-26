<?php

namespace Tests\Feature;

use App\Data\CatalogImport\DatabaseBackupInvocation;
use App\Data\CatalogImport\DatabaseBackupRequest;
use App\Data\CatalogImport\VerifiedDatabaseBackup;
use App\Models\CatalogImportItem;
use App\Models\CatalogImportRun;
use App\Services\CatalogImport\DatabaseBackup\DatabaseBackupException;
use App\Services\CatalogImport\DatabaseBackup\DatabaseBackupService;
use App\Services\CatalogImport\DatabaseBackup\DatabaseDumpRunner;
use App\Services\CatalogImport\DatabaseBackup\GzipBackupArchive;
use App\Services\CatalogImport\Publication\CatalogImportBackupArtifactVerifier;
use App\Services\CatalogImport\Publication\CatalogImportDatabaseBackupVerifier;
use App\Services\CatalogImport\Publication\CatalogImportImagePublisher;
use App\Services\CatalogImport\Publication\CatalogImportMutationLock;
use App\Services\CatalogImport\Publication\CatalogImportPublicationException;
use App\Services\CatalogImport\Publication\CatalogImportPublicationPreflight;
use App\Services\CatalogImport\Publication\CatalogImportPublisher;
use App\Services\CatalogImport\Publication\CatalogImportSitemapGenerator;
use DateTimeImmutable;
use Illuminate\Database\Events\TransactionCommitted;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CatalogImportPublicationTestCase;

class CatalogImportPublishTest extends CatalogImportPublicationTestCase
{
    private string $backupDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->backupDirectory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'publish-backup-'.bin2hex(random_bytes(8));
        mkdir($this->backupDirectory, 0700, true);
        config()->set('catalog-import-publication.enabled', true);
        config()->set('catalog-import-backup.destination', $this->backupDirectory);
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->backupDirectory);

        parent::tearDown();
    }

    public function test_feature_gate_refuses_before_backup_or_catalog_mutation(): void
    {
        $this->seedCatalogRoots();
        $run = $this->seedReviewedRun();
        config()->set('catalog-import-publication.enabled', false);
        $backup = $this->backupService();

        try {
            $this->publisher($backup)->publish($run);
            $this->fail('Publication must be disabled by default.');
        } catch (CatalogImportPublicationException $exception) {
            $this->assertStringContainsString('RIMSKIE_IMPORT_PUBLICATION_ENABLED', $exception->getMessage());
        }

        $this->assertSame(0, $backup->calls);
        $this->assertSame(0, DB::table('products')->count());
        $this->assertSame(0, DB::table('subcategories')->where('is_import_collection', true)->count());
    }

    public function test_feature_gate_environment_value_fails_closed_on_typo(): void
    {
        $previous = getenv('RIMSKIE_IMPORT_PUBLICATION_ENABLED');
        putenv('RIMSKIE_IMPORT_PUBLICATION_ENABLED=tru');

        try {
            /** @var array{enabled: bool} $publicationConfig */
            $publicationConfig = require config_path('catalog-import-publication.php');
        } finally {
            $previous === false
                ? putenv('RIMSKIE_IMPORT_PUBLICATION_ENABLED')
                : putenv('RIMSKIE_IMPORT_PUBLICATION_ENABLED='.$previous);
        }

        $this->assertFalse($publicationConfig['enabled']);
    }

    public function test_verified_backup_is_recorded_before_any_catalog_mutation_and_mapping_is_private(): void
    {
        [$categoryId, $baseSubcategoryId] = $this->seedCatalogRoots();
        $run = $this->seedReviewedRun();
        $backup = $this->backupService(function (DatabaseBackupRequest $request) use ($run): void {
            $this->assertSame('full-run-001', $request->runId);
            $this->assertSame('rimskie.com', $request->provider);
            $this->assertSame(0, DB::table('products')->count());
            $this->assertSame(0, DB::table('subcategories')->where('is_import_collection', true)->count());
            $this->assertNull($run->fresh()->backup_created_at);
        });
        $sitemap = new RecordingCatalogImportSitemapGenerator;

        $result = $this->publisher($backup, $sitemap)->publish($run);

        $this->assertTrue($result->catalogPublished);
        $this->assertTrue($result->sitemapGenerated);
        $this->assertFalse($result->noOp);
        $this->assertSame(1, $backup->calls);
        $this->assertSame(1, $sitemap->calls);

        $publishedRun = $run->fresh();
        $this->assertSame(CatalogImportRun::STATUS_PUBLISHED, $publishedRun->status);
        $this->assertNotNull($publishedRun->backup_created_at);
        $this->assertFileExists($publishedRun->backup_path);
        $this->assertFileExists($publishedRun->backup_manifest_path);

        $this->assertSame(46, DB::table('subcategories')->where('is_import_collection', true)->count());
        $collection = DB::table('subcategories')->where('import_run_id', $run->id)->orderBy('id')->first();
        $this->assertNotNull($collection);
        $this->assertSame($categoryId, (int) $collection->category_id);
        $this->assertSame('1', (string) $collection->show_in_catalog);
        $this->assertSame('0', (string) $collection->show_in_menu);

        $product = DB::table('products')->where('import_run_id', $run->id)->first();
        $this->assertNotNull($product);
        $this->assertSame($categoryId, (int) $product->category_id);
        $this->assertSame($baseSubcategoryId, (int) $product->subcategory_id);
        $this->assertSame('belaya-rimskaya-shtora-11889', $product->slug);
        $this->assertSame('2708', rtrim(rtrim((string) $product->source_price, '0'), '.'));
        $this->assertSame('0', (string) $product->calculator_enabled);
        $this->assertNull($product->min_price);
        $this->assertNull($product->min_price_updated_at);
        $this->assertNull($product->min_price_error);
        $this->assertSame('storage/catalog-imports/full-run-001/images/11889.webp', $product->image_path);
        $this->assertNull($product->image_thumb_path);
        Storage::disk('public')->assertExists('catalog-imports/full-run-001/images/11889.webp');
        $this->assertSame($this->validWebp, Storage::disk('public')->get('catalog-imports/full-run-001/images/11889.webp'));

        $this->assertSame(46, DB::table('catalog_collection_product')->count());
        $this->assertSame(1, DB::table('catalog_product_attribute_value')->count());
        $this->assertSame(
            [$run->id],
            DB::table('catalog_collection_product')->distinct()->pluck('catalog_import_run_id')->all(),
        );
        $this->assertSame(
            [$run->id],
            DB::table('catalog_product_attribute_value')->distinct()->pluck('catalog_import_run_id')->all(),
        );
        $this->assertNotNull($run->items()->firstOrFail()->publication_snapshot);
        $this->assertNotNull($run->sources()->firstOrFail()->publication_snapshot);
        $this->assertSame(CatalogImportItem::STATUS_PUBLISHED, $run->items()->firstOrFail()->review_status);
    }

    public function test_backup_failure_leaves_catalog_and_public_media_untouched(): void
    {
        $this->seedCatalogRoots();
        $run = $this->seedReviewedRun();
        $backup = $this->backupService(fail: true);

        $this->expectException(DatabaseBackupException::class);
        try {
            $this->publisher($backup)->publish($run);
        } finally {
            $this->assertSame(0, DB::table('products')->count());
            $this->assertSame(0, DB::table('subcategories')->where('is_import_collection', true)->count());
            $this->assertSame([], Storage::disk('public')->allFiles());
            $this->assertNull($run->fresh()->backup_created_at);
        }
    }

    public function test_warning_acknowledgement_is_written_only_after_verified_backup(): void
    {
        $this->seedCatalogRoots();
        $run = $this->seedReviewedRun();
        $run->items()->firstOrFail()->update(['warnings' => ['reviewed wording choice']]);
        $backup = $this->backupService(function () use ($run): void {
            $beforeBackup = $run->fresh();
            $this->assertNull($beforeBackup->warnings_acknowledged_at);
            $this->assertNull($beforeBackup->warnings_acknowledged_by);
            $this->assertNull($beforeBackup->warnings_acknowledged_sha256);
        });

        $this->publisher($backup)->publish($run, 'release-operator');

        $published = $run->fresh();
        $this->assertNotNull($published->backup_created_at);
        $this->assertNotNull($published->warnings_acknowledged_at);
        $this->assertSame('release-operator', $published->warnings_acknowledged_by);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/D', $published->warnings_acknowledged_sha256);
    }

    public function test_restart_after_durable_media_plan_reuses_backup_and_completes_publication(): void
    {
        $this->seedCatalogRoots();
        $run = $this->seedReviewedRun();
        $backup = $this->backupService();
        $publisher = $this->publisher($backup);
        $crashed = false;
        CatalogImportRun::updated(static function (CatalogImportRun $updated) use (&$crashed): void {
            if (! $crashed && is_array($updated->publication_journal)) {
                $crashed = true;
                throw new \RuntimeException('Simulated process stop after durable publication media plan.');
            }
        });

        try {
            $publisher->publish($run);
            $this->fail('The first attempt must stop immediately after recording its durable media plan.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('durable publication media plan', $exception->getMessage());
        }

        $planned = $run->fresh();
        $this->assertNotNull($planned->backup_created_at);
        $this->assertIsArray($planned->publication_journal);
        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertSame(0, DB::table('products')->where('import_run_id', $run->id)->count());

        $result = $publisher->publish($planned);

        $this->assertTrue($result->catalogPublished);
        $this->assertSame(1, $backup->calls);
        $this->assertNull($run->fresh()->publication_journal);
        Storage::disk('public')->assertExists('catalog-imports/full-run-001/images/11889.webp');
    }

    public function test_restart_refuses_matching_file_that_appeared_after_absent_media_plan(): void
    {
        $this->seedCatalogRoots();
        $run = $this->seedReviewedRun();
        $backup = $this->backupService();
        $publisher = $this->publisher($backup);
        $crashed = false;
        CatalogImportRun::updated(static function (CatalogImportRun $updated) use (&$crashed): void {
            if (! $crashed && is_array($updated->publication_journal)) {
                $crashed = true;
                throw new \RuntimeException('Simulated stop after publication plan.');
            }
        });
        try {
            $publisher->publish($run);
        } catch (\RuntimeException) {
            // Durable plan is the simulated restart boundary.
        }
        $path = 'catalog-imports/full-run-001/images/11889.webp';
        Storage::disk('public')->put($path, $this->validWebp);

        try {
            $publisher->publish($run->fresh());
            $this->fail('A matching racer file without durable created ownership must not be adopted.');
        } catch (CatalogImportPublicationException $exception) {
            $this->assertStringContainsString('manual verification', $exception->getMessage());
        }

        $this->assertSame(1, $backup->calls);
        $this->assertNotNull($run->fresh()->publication_journal);
        $this->assertSame('publication_media_verification_required', $run->fresh()->publication_error);
        $this->assertSame(0, DB::table('products')->where('import_run_id', $run->id)->count());
        Storage::disk('public')->assertExists($path);
    }

    public function test_restart_with_durably_created_media_keeps_created_ownership_for_rollback(): void
    {
        $this->seedCatalogRoots();
        $run = $this->seedReviewedRun();
        $backup = $this->backupService();
        $publisher = $this->publisher($backup);
        $crashed = false;
        CatalogImportRun::updated(static function (CatalogImportRun $updated) use (&$crashed): void {
            if (! $crashed && is_array($updated->publication_journal)) {
                $crashed = true;
                throw new \RuntimeException('Simulated stop after publication plan.');
            }
        });
        try {
            $publisher->publish($run);
        } catch (\RuntimeException) {
            // Durable plan is the simulated restart boundary.
        }
        $path = 'catalog-imports/full-run-001/images/11889.webp';
        Storage::disk('public')->put($path, $this->validWebp);
        $stat = lstat(Storage::disk('public')->path($path));
        $planned = $run->fresh();
        $journal = $planned->publication_journal;
        $journal['media'][0]['created'] = true;
        $journal['media'][0]['creation_identity'] = [
            'dev' => (int) $stat['dev'],
            'ino' => (int) $stat['ino'],
        ];
        $journal['media'][0]['status'] = 'published';
        $journal['status'] = 'ready';
        $planned->update(['publication_journal' => $this->reverseAssociativeKeys($journal)]);

        $result = $publisher->publish($planned->fresh());

        $this->assertTrue($result->catalogPublished);
        $this->assertSame(1, $backup->calls);
        $this->assertNull($run->fresh()->publication_journal);
        $this->assertTrue($run->items()->firstOrFail()->publication_snapshot['media'][0]['created']);
        Storage::disk('public')->assertExists($path);
    }

    public function test_durable_publication_journal_records_file_identity_for_restart_compensation(): void
    {
        $this->seedCatalogRoots();
        $run = $this->seedReviewedRun();
        $backup = $this->backupService();
        $publisher = $this->publisher($backup);
        $crashed = false;
        CatalogImportRun::updated(static function (CatalogImportRun $updated) use (&$crashed): void {
            $entry = $updated->publication_journal['media'][0] ?? null;
            if (! $crashed && is_array($entry) && ($entry['status'] ?? null) === 'published') {
                $crashed = true;
                throw new \RuntimeException('Simulated stop after durable file ownership recording.');
            }
        });

        try {
            $publisher->publish($run);
            $this->fail('The first attempt must stop after durable file ownership recording.');
        } catch (CatalogImportPublicationException $exception) {
            $this->assertStringContainsString('manual verification', $exception->getMessage());
        }

        $journal = $run->fresh()->publication_journal;
        $identity = $journal['media'][0]['creation_identity'] ?? null;
        $this->assertIsArray($identity);
        $this->assertIsInt($identity['dev'] ?? null);
        $this->assertIsInt($identity['ino'] ?? null);
        Storage::disk('public')->assertExists('catalog-imports/full-run-001/images/11889.webp');

        DB::statement(
            'CREATE TRIGGER reject_restart_import BEFORE INSERT ON products '
            ."BEGIN SELECT RAISE(ABORT, 'controlled-restart-failure'); END"
        );
        try {
            $publisher->publish($run->fresh());
            $this->fail('The resumed catalog mutation must fail for compensation coverage.');
        } catch (CatalogImportPublicationException) {
            // The durable dev/ino ownership must allow safe restart compensation.
        }

        Storage::disk('public')->assertMissing('catalog-imports/full-run-001/images/11889.webp');
        $this->assertNull($run->fresh()->publication_journal);
        $this->assertSame('publication_failed', $run->fresh()->publication_error);
    }

    public function test_backup_failure_leaves_warning_acknowledgement_untouched(): void
    {
        $this->seedCatalogRoots();
        $run = $this->seedReviewedRun();
        $run->items()->firstOrFail()->update(['warnings' => ['reviewed wording choice']]);
        $backup = $this->backupService(
            beforeCreate: function () use ($run): void {
                $this->assertNull($run->fresh()->warnings_acknowledged_at);
            },
            fail: true,
        );

        try {
            $this->publisher($backup)->publish($run, 'release-operator');
            $this->fail('A controlled backup failure must abort publication.');
        } catch (DatabaseBackupException) {
            // Expected: no acknowledgement or catalog mutation may precede the backup.
        }

        $failed = $run->fresh();
        $this->assertNull($failed->warnings_acknowledged_at);
        $this->assertNull($failed->warnings_acknowledged_by);
        $this->assertNull($failed->warnings_acknowledged_sha256);
        $this->assertSame(0, DB::table('products')->count());
    }

    public function test_catalog_roots_are_revalidated_after_backup_before_mapping(): void
    {
        [, $oldBaseSubcategoryId] = $this->seedCatalogRoots();
        $run = $this->seedReviewedRun();
        $newBaseSubcategoryId = null;
        $backup = $this->backupService(function () use ($oldBaseSubcategoryId, &$newBaseSubcategoryId): void {
            DB::table('subcategories')->where('id', $oldBaseSubcategoryId)->delete();
            $categoryId = (int) DB::table('categories')->where('slug', 'story')->value('id');
            $newBaseSubcategoryId = DB::table('subcategories')->insertGetId([
                'category_id' => $categoryId,
                'title' => 'Римские шторы',
                'slug' => 'rimskieshtory',
                'show_in_menu' => true,
                'show_in_catalog' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        $this->publisher($backup)->publish($run);

        $this->assertNotSame($oldBaseSubcategoryId, $newBaseSubcategoryId);
        $this->assertSame(
            $newBaseSubcategoryId,
            (int) DB::table('products')->where('import_run_id', $run->id)->value('subcategory_id'),
        );
    }

    public function test_catalog_failure_persists_only_safe_diagnostic_and_compensates_media(): void
    {
        $this->seedCatalogRoots();
        $run = $this->seedReviewedRun();
        $secret = 'TOP_SECRET_BINDING_2708';
        $backup = $this->backupService(function () use ($secret): void {
            DB::statement(
                'CREATE TRIGGER reject_import_product BEFORE INSERT ON products '
                ."BEGIN SELECT RAISE(ABORT, '$secret'); END"
            );
        });

        try {
            $this->publisher($backup)->publish($run);
            $this->fail('The controlled catalog mutation must fail.');
        } catch (CatalogImportPublicationException $exception) {
            $this->assertStringNotContainsString($secret, $exception->getMessage());
        }

        $this->assertSame('publication_failed', $run->fresh()->publication_error);
        $this->assertStringNotContainsString($secret, (string) $run->fresh()->publication_error);
        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertSame(0, DB::table('products')->count());
    }

    public function test_commit_success_followed_by_transport_exception_preserves_committed_media(): void
    {
        $this->seedCatalogRoots();
        $run = $this->seedReviewedRun();
        $backup = $this->backupService();
        $sitemap = new RecordingCatalogImportSitemapGenerator;
        $publisher = $this->publisher($backup, $sitemap);
        Event::listen(TransactionCommitted::class, static function (): never {
            Event::forget(TransactionCommitted::class);

            throw new \RuntimeException('Connection lost after COMMIT acknowledgement.');
        });

        try {
            $result = $publisher->publish($run);
        } catch (CatalogImportPublicationException $exception) {
            $this->fail('A confirmed committed publication must be reconciled: '.$exception->getMessage());
        } finally {
            Event::forget(TransactionCommitted::class);
        }

        $this->assertTrue($result->catalogPublished);
        $this->assertSame(CatalogImportRun::STATUS_PUBLISHED, $run->fresh()->status);
        $this->assertNull($run->fresh()->publication_error);
        $this->assertSame(1, DB::table('products')->where('import_run_id', $run->id)->count());
        Storage::disk('public')->assertExists('catalog-imports/full-run-001/images/11889.webp');
        $this->assertSame(1, $backup->calls);
        $this->assertSame(1, $sitemap->calls);
    }

    public function test_uncertain_post_link_media_is_preserved_with_manual_verification_diagnostic(): void
    {
        $this->seedCatalogRoots();
        $run = $this->seedReviewedRun();
        $images = new CatalogImportImagePublisher(
            afterPublicLink: static function (string $path): void {
                Storage::disk('public')->put($path, 'changed-after-link');
            },
        );

        try {
            $this->publisher($this->backupService(), images: $images)->publish($run);
            $this->fail('Uncertain created media ownership must stop publication.');
        } catch (CatalogImportPublicationException $exception) {
            $this->assertStringContainsString('manual verification', $exception->getMessage());
        }

        $this->assertSame('publication_media_verification_required', $run->fresh()->publication_error);
        $this->assertSame(0, DB::table('products')->count());
        $path = 'catalog-imports/full-run-001/images/11889.webp';
        Storage::disk('public')->assertExists($path);
        $this->assertSame('changed-after-link', Storage::disk('public')->get($path));
    }

    public function test_conflicting_public_image_aborts_without_overwriting_or_backing_up(): void
    {
        $this->seedCatalogRoots();
        $run = $this->seedReviewedRun();
        $destination = 'catalog-imports/full-run-001/images/11889.webp';
        Storage::disk('public')->put($destination, 'unowned-content');
        $backup = $this->backupService();

        try {
            $this->publisher($backup)->publish($run);
            $this->fail('A public image collision must abort publication.');
        } catch (CatalogImportPublicationException $exception) {
            $this->assertStringContainsString('public image', $exception->getMessage());
        }

        $this->assertSame('unowned-content', Storage::disk('public')->get($destination));
        $this->assertSame(0, $backup->calls);
        $this->assertSame(0, DB::table('products')->count());
    }

    public function test_postcommit_sitemap_failure_is_retryable_without_second_backup_or_duplicate_snapshot(): void
    {
        $this->seedCatalogRoots();
        $run = $this->seedReviewedRun();
        $backup = $this->backupService();
        $sitemap = new RecordingCatalogImportSitemapGenerator(failuresRemaining: 1);
        $publisher = $this->publisher($backup, $sitemap);

        $first = $publisher->publish($run);
        $snapshot = $run->items()->firstOrFail()->publication_snapshot;

        $this->assertTrue($first->catalogPublished);
        $this->assertFalse($first->sitemapGenerated);
        $this->assertSame(CatalogImportRun::STATUS_PUBLISHED, $run->fresh()->status);
        $this->assertNotNull($run->fresh()->sitemap_error);

        $second = $publisher->publish($run->fresh());

        $this->assertTrue($second->catalogPublished);
        $this->assertTrue($second->sitemapGenerated);
        $this->assertTrue($second->noOp);
        $this->assertSame(1, $backup->calls);
        $this->assertSame(2, $sitemap->calls);
        $this->assertSame(1, DB::table('products')->count());
        $this->assertSame(46, DB::table('subcategories')->where('is_import_collection', true)->count());
        $this->assertSame($snapshot, $run->items()->firstOrFail()->publication_snapshot);
        $this->assertNotNull($run->fresh()->sitemap_generated_at);
        $this->assertNull($run->fresh()->sitemap_error);
    }

    public function test_sitemap_retry_refuses_when_a_published_staging_source_was_deleted(): void
    {
        $this->seedCatalogRoots();
        $run = $this->seedReviewedRun();
        $backup = $this->backupService();
        $sitemap = new RecordingCatalogImportSitemapGenerator;
        $publisher = $this->publisher($backup, $sitemap);
        $publisher->publish($run);
        $run->sources()->orderBy('id')->firstOrFail()->delete();

        try {
            $publisher->publish($run->fresh());
            $this->fail('A retry must not accept an incomplete source ownership set.');
        } catch (CatalogImportPublicationException $exception) {
            $this->assertStringContainsString('ownership set', $exception->getMessage());
        }

        $this->assertSame(1, $backup->calls);
        $this->assertSame(1, $sitemap->calls);
        $this->assertSame(46, DB::table('subcategories')->where('import_run_id', $run->id)->count());
    }

    public function test_sitemap_retry_refuses_when_a_published_staging_item_was_deleted(): void
    {
        $this->seedCatalogRoots();
        $run = $this->seedReviewedRun();
        $backup = $this->backupService();
        $sitemap = new RecordingCatalogImportSitemapGenerator;
        $publisher = $this->publisher($backup, $sitemap);
        $publisher->publish($run);
        $run->items()->firstOrFail()->delete();

        try {
            $publisher->publish($run->fresh());
            $this->fail('A retry must not accept an incomplete item ownership set.');
        } catch (CatalogImportPublicationException $exception) {
            $this->assertStringContainsString('ownership set', $exception->getMessage());
        }

        $this->assertSame(1, $backup->calls);
        $this->assertSame(1, $sitemap->calls);
        $this->assertSame(1, DB::table('products')->where('import_run_id', $run->id)->count());
    }

    public function test_sitemap_retry_refuses_an_orphan_row_claiming_run_ownership(): void
    {
        $this->seedCatalogRoots();
        $run = $this->seedReviewedRun();
        $backup = $this->backupService();
        $sitemap = new RecordingCatalogImportSitemapGenerator;
        $publisher = $this->publisher($backup, $sitemap);
        $publisher->publish($run);
        DB::table('subcategories')->insert([
            'category_id' => DB::table('categories')->where('slug', 'story')->value('id'),
            'title' => 'Orphan import collection',
            'slug' => 'orphan-import-collection',
            'show_in_catalog' => true,
            'show_in_menu' => false,
            'is_import_collection' => true,
            'import_run_id' => $run->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            $publisher->publish($run->fresh());
            $this->fail('A retry must reject rows missing from immutable ownership snapshots.');
        } catch (CatalogImportPublicationException $exception) {
            $this->assertStringContainsString('ownership set', $exception->getMessage());
        }

        $this->assertSame(1, $backup->calls);
        $this->assertSame(1, $sitemap->calls);
    }

    public function test_sitemap_retry_requires_exactly_one_owned_media_snapshot_per_product(): void
    {
        $this->seedCatalogRoots();
        $run = $this->seedReviewedRun();
        $publisher = $this->publisher($this->backupService());
        $publisher->publish($run);
        $item = $run->items()->firstOrFail();
        $snapshot = $item->publication_snapshot;
        $snapshot['media'] = [];
        $item->update(['publication_snapshot' => $snapshot]);

        $this->expectException(CatalogImportPublicationException::class);
        $this->expectExceptionMessage('media ownership');

        $publisher->publish($run->fresh());
    }

    public function test_sitemap_retry_accepts_mysql_style_reordered_snapshot_object_keys(): void
    {
        $this->seedCatalogRoots();
        $run = $this->seedReviewedRun();
        $publisher = $this->publisher($this->backupService());
        $publisher->publish($run);
        foreach ($run->sources()->get() as $source) {
            $source->update([
                'publication_snapshot' => $this->reverseAssociativeKeys($source->publication_snapshot),
            ]);
        }
        foreach ($run->items()->get() as $item) {
            $item->update([
                'publication_snapshot' => $this->reverseAssociativeKeys($item->publication_snapshot),
            ]);
        }

        try {
            $result = $publisher->publish($run->fresh());
        } catch (CatalogImportPublicationException $exception) {
            $this->fail('Associative JSON key ordering must not change ownership equality: '.$exception->getMessage());
        }

        $this->assertTrue($result->catalogPublished);
        $this->assertTrue($result->noOp);
    }

    public function test_sitemap_retry_refuses_when_recorded_backup_archive_was_tampered(): void
    {
        $this->seedCatalogRoots();
        $run = $this->seedReviewedRun();
        $backup = $this->backupService();
        $sitemap = new RecordingCatalogImportSitemapGenerator(failuresRemaining: 1);
        $publisher = $this->publisher($backup, $sitemap);
        $publisher->publish($run);
        file_put_contents($run->fresh()->backup_path, 'tampered archive');

        $this->expectException(CatalogImportPublicationException::class);
        $this->expectExceptionMessage('recorded verified backup');

        $publisher->publish($run->fresh());
    }

    public function test_sitemap_retry_refuses_when_recorded_backup_manifest_was_tampered(): void
    {
        $this->seedCatalogRoots();
        $run = $this->seedReviewedRun();
        $publisher = $this->publisher(
            $this->backupService(),
            new RecordingCatalogImportSitemapGenerator(failuresRemaining: 1),
        );
        $publisher->publish($run);
        $published = $run->fresh();
        $manifest = json_decode(
            (string) file_get_contents($published->backup_manifest_path),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        $manifest['timestamp_utc'] = '2030-01-01T00:00:00.000000Z';
        file_put_contents(
            $published->backup_manifest_path,
            json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
        );

        $this->expectException(CatalogImportPublicationException::class);
        $this->expectExceptionMessage('recorded verified backup');

        $publisher->publish($published);
    }

    public function test_lying_verified_backup_value_object_is_rejected_before_catalog_mutation(): void
    {
        $this->seedCatalogRoots();
        $run = $this->seedReviewedRun();
        $backup = $this->backupService(lieAboutRawHash: true);

        $this->expectException(CatalogImportPublicationException::class);
        try {
            $this->publisher($backup)->publish($run);
        } finally {
            $this->assertSame(0, DB::table('products')->count());
            $this->assertNull($run->fresh()->backup_created_at);
        }
    }

    public function test_verified_backup_outside_configured_private_root_is_rejected(): void
    {
        $this->seedCatalogRoots();
        $run = $this->seedReviewedRun();
        $outside = sys_get_temp_dir().DIRECTORY_SEPARATOR.'outside-publish-backup-'.bin2hex(random_bytes(8));
        mkdir($outside, 0700, true);

        try {
            $this->publisher($this->backupService(directory: $outside))->publish($run);
            $this->fail('A valid-looking backup outside the configured private root must be rejected.');
        } catch (CatalogImportPublicationException $exception) {
            $this->assertStringContainsString('private backup root', $exception->getMessage());
        } finally {
            (new Filesystem)->deleteDirectory($outside);
        }

        $this->assertNull($run->fresh()->backup_created_at);
        $this->assertSame(0, DB::table('products')->count());
    }

    public function test_verified_backup_path_swap_during_open_is_rejected(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('Windows keeps an unlinked open path reserved; junction traversal is covered separately.');
        }
        $backup = $this->backupService()->create(new DatabaseBackupRequest(
            runId: 'full-run-001',
            provider: 'rimskie.com',
            connectionName: (string) config('database.default'),
            connection: (array) config('database.connections.'.config('database.default'), []),
        ));
        $openedPath = $backup->archivePath.'.opened';
        $replacementPath = $backup->archivePath.'.replacement';
        copy($backup->archivePath, $replacementPath);
        chmod($replacementPath, 0600);
        $swapped = false;
        $artifacts = new CatalogImportBackupArtifactVerifier(
            static function (string $path, string $label) use (&$swapped, $openedPath, $replacementPath): void {
                if ($swapped || ! str_contains($label, 'archive')) {
                    return;
                }
                $swapped = true;
                rename($path, $openedPath);
                rename($replacementPath, $path);
            },
        );

        try {
            (new CatalogImportDatabaseBackupVerifier($artifacts))->verifyCreated(
                $backup,
                'full-run-001',
                'rimskie.com',
            );
            $this->fail('A path replacement after opening the verified artifact must be rejected.');
        } catch (CatalogImportPublicationException $exception) {
            $this->assertStringContainsString('identity changed', $exception->getMessage());
        }

        $this->assertTrue($swapped);
        $this->assertFileExists($backup->archivePath);
    }

    public function test_verified_backup_destination_inside_a_public_root_is_rejected(): void
    {
        $this->seedCatalogRoots();
        $run = $this->seedReviewedRun();
        config()->set('catalog-import-backup.public_roots', [$this->backupDirectory]);

        try {
            $this->publisher($this->backupService())->publish($run);
            $this->fail('A database backup below a configured public root must be rejected.');
        } catch (CatalogImportPublicationException $exception) {
            $this->assertStringContainsString('public root', $exception->getMessage());
        }

        $this->assertNull($run->fresh()->backup_created_at);
        $this->assertSame(0, DB::table('products')->count());
    }

    public function test_sitemap_retry_rejects_world_readable_backup_artifact_on_posix(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('POSIX permission bits are not enforced on Windows.');
        }
        $this->seedCatalogRoots();
        $run = $this->seedReviewedRun();
        $publisher = $this->publisher($this->backupService());
        $publisher->publish($run);
        chmod($run->fresh()->backup_path, 0644);

        $this->expectException(CatalogImportPublicationException::class);
        $this->expectExceptionMessage('permissions are not private');

        $publisher->publish($run->fresh());
    }

    public function test_sitemap_retry_rejects_recorded_backup_paths_moved_outside_private_root(): void
    {
        $this->seedCatalogRoots();
        $run = $this->seedReviewedRun();
        $publisher = $this->publisher($this->backupService());
        $publisher->publish($run);
        $published = $run->fresh();
        $outside = sys_get_temp_dir().DIRECTORY_SEPARATOR.'outside-recorded-backup-'.bin2hex(random_bytes(8));
        mkdir($outside, 0700, true);
        $outsideArchive = $outside.DIRECTORY_SEPARATOR.'backup.sql.gz';
        $outsideManifest = $outside.DIRECTORY_SEPARATOR.'backup.json';
        copy($published->backup_path, $outsideArchive);
        copy($published->backup_manifest_path, $outsideManifest);
        $published->update([
            'backup_path' => $outsideArchive,
            'backup_manifest_path' => $outsideManifest,
        ]);

        try {
            $publisher->publish($published->fresh());
            $this->fail('A retry must not trust recorded backup paths outside the private root.');
        } catch (CatalogImportPublicationException $exception) {
            $this->assertStringContainsString('private backup root', $exception->getMessage());
        } finally {
            (new Filesystem)->deleteDirectory($outside);
        }
    }

    public function test_verified_backup_rejects_windows_junction_ancestor_inside_private_root(): void
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            $this->markTestSkipped('This regression covers Windows junction traversal.');
        }
        $this->seedCatalogRoots();
        $run = $this->seedReviewedRun();
        $outside = sys_get_temp_dir().DIRECTORY_SEPARATOR.'junction-backup-target-'.bin2hex(random_bytes(8));
        $junction = $this->backupDirectory.DIRECTORY_SEPARATOR.'escape';
        mkdir($outside, 0700, true);
        if (! $this->createJunction($junction, $outside)) {
            (new Filesystem)->deleteDirectory($outside);
            $this->markTestSkipped('This host does not permit creating a test junction.');
        }

        try {
            $this->publisher($this->backupService(directory: $junction))->publish($run);
            $this->fail('A backup below a junction must be rejected.');
        } catch (CatalogImportPublicationException $exception) {
            $this->assertStringContainsString('junction', $exception->getMessage());
            $this->assertSame(0, DB::table('products')->count());
        } finally {
            @rmdir($junction);
            (new Filesystem)->deleteDirectory($outside);
        }
    }

    public function test_product_serialization_hides_all_private_donor_and_ownership_fields(): void
    {
        $this->seedCatalogRoots();
        $run = $this->seedReviewedRun();
        $this->publisher($this->backupService())->publish($run);

        $serialized = $run->items()->firstOrFail()->product->toArray();

        foreach (['source_provider', 'source_external_id', 'source_url', 'source_price', 'import_run_id'] as $field) {
            $this->assertArrayNotHasKey($field, $serialized);
        }
        $this->assertStringNotContainsString('2708', json_encode($serialized, JSON_THROW_ON_ERROR));
        $this->assertStringNotContainsString('rimskie.com/products', json_encode($serialized, JSON_THROW_ON_ERROR));
    }

    public function test_published_retry_rejects_changed_created_ownership_flag(): void
    {
        $this->seedCatalogRoots();
        $run = $this->seedReviewedRun();
        $backup = $this->backupService();
        $publisher = $this->publisher($backup);
        $publisher->publish($run);
        $run->items()->firstOrFail()->update(['created_product' => false]);

        $this->expectException(CatalogImportPublicationException::class);
        $this->expectExceptionMessage('ownership snapshot');

        $publisher->publish($run->fresh());
    }

    public function test_published_retry_rejects_same_bytes_media_replacement(): void
    {
        $this->seedCatalogRoots();
        $run = $this->seedReviewedRun();
        $publisher = $this->publisher($this->backupService());
        $publisher->publish($run);
        $path = 'catalog-imports/full-run-001/images/11889.webp';
        unlink(Storage::disk('public')->path($path));
        Storage::disk('public')->put($path, $this->validWebp);

        try {
            $publisher->publish($run->fresh());
            $this->fail('A same-bytes replacement must not satisfy immutable media ownership.');
        } catch (CatalogImportPublicationException $exception) {
            $this->assertStringContainsString('identity', $exception->getMessage());
        }

        Storage::disk('public')->assertExists($path);
        $this->assertSame($this->validWebp, Storage::disk('public')->get($path));
    }

    private function publisher(
        DatabaseBackupService $backup,
        ?CatalogImportSitemapGenerator $sitemap = null,
        ?CatalogImportImagePublisher $images = null,
    ): CatalogImportPublisher {
        $images ??= new CatalogImportImagePublisher;

        return new CatalogImportPublisher(
            preflight: new CatalogImportPublicationPreflight($images),
            backup: $backup,
            images: $images,
            lock: new CatalogImportMutationLock,
            sitemap: $sitemap ?? new RecordingCatalogImportSitemapGenerator,
        );
    }

    private function backupService(
        ?\Closure $beforeCreate = null,
        bool $fail = false,
        bool $lieAboutRawHash = false,
        ?string $directory = null,
    ): RecordingPublicationBackupService {
        return new RecordingPublicationBackupService(
            directory: $directory ?? $this->backupDirectory,
            beforeCreate: $beforeCreate,
            fail: $fail,
            lieAboutRawHash: $lieAboutRawHash,
        );
    }

    private function createJunction(string $junction, string $target): bool
    {
        $command = "New-Item -ItemType Junction -Path '"
            .str_replace("'", "''", $junction)."' -Target '"
            .str_replace("'", "''", $target)."' | Out-Null";
        $process = proc_open(
            ['powershell.exe', '-NoProfile', '-Command', $command],
            [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']],
            $pipes,
        );
        if (! is_resource($process)) {
            return false;
        }
        fclose($pipes[0]);
        stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        return proc_close($process) === 0 && is_dir($junction);
    }

    private function reverseAssociativeKeys(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }
        if (array_is_list($value)) {
            return array_map(fn (mixed $entry): mixed => $this->reverseAssociativeKeys($entry), $value);
        }

        $reordered = [];
        foreach (array_reverse(array_keys($value)) as $key) {
            $reordered[$key] = $this->reverseAssociativeKeys($value[$key]);
        }

        return $reordered;
    }
}

class RecordingPublicationBackupService extends DatabaseBackupService
{
    public int $calls = 0;

    public function __construct(
        private readonly string $directory,
        private readonly ?\Closure $beforeCreate = null,
        private readonly bool $fail = false,
        private readonly bool $lieAboutRawHash = false,
    ) {
        parent::__construct(
            runner: new class implements DatabaseDumpRunner
            {
                public function run(DatabaseBackupInvocation $invocation): void
                {
                    throw new \LogicException('The overridden backup service must not call its parent runner.');
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
            throw new DatabaseBackupException('Controlled backup failure.');
        }

        $sql = "CREATE TABLE `products` (`id` bigint);\n";
        $archivePath = $this->directory.DIRECTORY_SEPARATOR.'backup-'.$this->calls.'.sql.gz';
        $archive = gzopen($archivePath, 'wb6');
        gzwrite($archive, $sql);
        gzclose($archive);
        if (PHP_OS_FAMILY !== 'Windows') {
            chmod($archivePath, 0600);
        }
        $manifestPath = $this->directory.DIRECTORY_SEPARATOR.'backup-'.$this->calls.'.json';
        $rawSha256 = hash('sha256', $sql);
        $rawSize = strlen($sql);
        $gzipSha256 = hash_file('sha256', $archivePath);
        $gzipSize = filesize($archivePath);
        $verifiedAt = new DateTimeImmutable('2026-08-26T10:15:30.123456Z');
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
        file_put_contents($manifestPath, json_encode($manifest, JSON_THROW_ON_ERROR));
        if (PHP_OS_FAMILY !== 'Windows') {
            chmod($manifestPath, 0600);
        }

        return new VerifiedDatabaseBackup(
            archivePath: $archivePath,
            manifestPath: $manifestPath,
            rawSha256: $this->lieAboutRawHash ? str_repeat('f', 64) : $rawSha256,
            rawSize: $rawSize,
            gzipSha256: $gzipSha256,
            gzipSize: $gzipSize,
            verifiedAt: $verifiedAt,
            manifest: $manifest,
        );
    }
}

class RecordingCatalogImportSitemapGenerator implements CatalogImportSitemapGenerator
{
    public int $calls = 0;

    public function __construct(public int $failuresRemaining = 0) {}

    public function generate(): void
    {
        $this->calls++;
        if ($this->failuresRemaining > 0) {
            $this->failuresRemaining--;
            throw new \RuntimeException('Controlled sitemap failure.');
        }
    }
}
