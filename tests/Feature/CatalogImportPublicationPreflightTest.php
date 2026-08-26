<?php

namespace Tests\Feature;

use App\Models\CatalogImportItem;
use App\Models\CatalogImportSource;
use App\Services\CatalogImport\Publication\CatalogImportPublicationException;
use App\Services\CatalogImport\Publication\CatalogImportPublicationPreflight;
use App\Services\CatalogImport\Publication\CatalogImportWarningAcknowledgement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CatalogImportPublicationTestCase;

class CatalogImportPublicationPreflightTest extends CatalogImportPublicationTestCase
{
    public function test_complete_reviewed_canonical_run_passes_preflight(): void
    {
        $this->seedCatalogRoots();
        $run = $this->seedReviewedRun();

        $report = app(CatalogImportPublicationPreflight::class)->inspect($run);

        $this->assertSame(46, $report->sourceCount);
        $this->assertSame(1, $report->itemCount);
        $this->assertSame(46, $report->membershipCount);
        $this->assertFalse($report->warningsAcknowledgementRequired);
    }

    public function test_bounded_collector_run_is_rejected(): void
    {
        $this->seedCatalogRoots();
        $run = $this->seedReviewedRun();
        $config = $run->config;
        $config['collector_config']['limits']['max_products'] = 1;
        $run->update(['config' => $config]);

        $this->expectException(CatalogImportPublicationException::class);
        $this->expectExceptionMessage('bounded collector run');

        app(CatalogImportPublicationPreflight::class)->inspect($run->fresh());
    }

    public function test_page_and_duplicate_counts_must_be_exact(): void
    {
        $this->seedCatalogRoots();
        $run = $this->seedReviewedRun();
        $run->update(['page_count' => 45, 'duplicate_count' => 1]);

        $this->expectException(CatalogImportPublicationException::class);
        $this->expectExceptionMessage('page and duplicate counts');

        app(CatalogImportPublicationPreflight::class)->inspect($run->fresh());
    }

    public function test_source_definition_review_and_membership_must_be_exact(): void
    {
        $this->seedCatalogRoots();
        $run = $this->seedReviewedRun();
        $source = $run->sources()->orderBy('sort_order')->firstOrFail();
        $source->update([
            'source_url' => 'https://rimskie.com/catalog/rimskie-shtory/changed',
            'review_status' => CatalogImportSource::REVIEW_NEEDS_REVIEW,
        ]);
        DB::table('catalog_import_item_source')
            ->where('import_source_id', $source->id)
            ->delete();

        $this->expectException(CatalogImportPublicationException::class);
        $this->expectExceptionMessage('canonical source definitions');

        app(CatalogImportPublicationPreflight::class)->inspect($run->fresh());
    }

    public function test_unacknowledged_rewrite_warning_is_rejected(): void
    {
        $this->seedCatalogRoots();
        $run = $this->seedReviewedRun();
        $run->items()->firstOrFail()->update(['warnings' => ['summary_out_of_bounds']]);

        $this->expectException(CatalogImportPublicationException::class);
        $this->expectExceptionMessage('warnings must be explicitly acknowledged');

        app(CatalogImportPublicationPreflight::class)->inspect($run->fresh());
    }

    public function test_acknowledged_warning_is_reported_but_allowed(): void
    {
        $this->seedCatalogRoots();
        $run = $this->seedReviewedRun();
        $run->items()->firstOrFail()->update(['warnings' => ['summary_out_of_bounds']]);
        app(CatalogImportWarningAcknowledgement::class)->acknowledge($run->fresh(), 'operator');

        $report = app(CatalogImportPublicationPreflight::class)->inspect($run->fresh());

        $this->assertSame(1, $report->warningCount);
        $this->assertFalse($report->warningsAcknowledgementRequired);
    }

    public function test_warning_acknowledgement_is_invalidated_when_exact_warning_set_changes(): void
    {
        $this->seedCatalogRoots();
        $run = $this->seedReviewedRun();
        $item = $run->items()->firstOrFail();
        $item->update(['warnings' => ['summary_out_of_bounds']]);
        app(CatalogImportWarningAcknowledgement::class)->acknowledge($run->fresh(), 'operator');
        $item->update(['warnings' => ['description_out_of_bounds', 'summary_out_of_bounds']]);

        $this->expectException(CatalogImportPublicationException::class);
        $this->expectExceptionMessage('warnings must be explicitly acknowledged');

        app(CatalogImportPublicationPreflight::class)->inspect($run->fresh());
    }

    public function test_private_image_path_rejects_an_inside_root_windows_junction(): void
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            $this->markTestSkipped('This regression covers Windows junction traversal.');
        }
        $this->seedCatalogRoots();
        $run = $this->seedReviewedRun();
        $item = $run->items()->firstOrFail();
        $disk = Storage::disk('local');
        $targetDirectory = $disk->path('junction-target');
        $junctionDirectory = $disk->path('catalog-imports/full-run-001/images');
        mkdir($targetDirectory, 0700, true);
        file_put_contents($targetDirectory.DIRECTORY_SEPARATOR.'11889.webp', $this->validWebp);
        unlink($disk->path($item->source_image_path));
        rmdir($junctionDirectory);
        $command = "New-Item -ItemType Junction -Path '"
            .str_replace("'", "''", $junctionDirectory)."' -Target '"
            .str_replace("'", "''", $targetDirectory)."' | Out-Null";
        $process = proc_open(
            ['powershell.exe', '-NoProfile', '-Command', $command],
            [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']],
            $pipes,
        );
        if (! is_resource($process)) {
            $this->markTestSkipped('Unable to start mklink for the junction regression.');
        }
        fclose($pipes[0]);
        stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        if (proc_close($process) !== 0 || ! is_dir($junctionDirectory)) {
            $this->markTestSkipped('This host does not permit creating a test junction.');
        }
        try {
            app(CatalogImportPublicationPreflight::class)->inspect($run->fresh());
            $this->fail('An inside-root junction must not satisfy canonical containment.');
        } catch (CatalogImportPublicationException $exception) {
            $this->assertStringContainsString('junction', $exception->getMessage());
        } finally {
            @rmdir($junctionDirectory);
        }
    }

    public function test_changed_or_non_webp_private_image_is_rejected(): void
    {
        $this->seedCatalogRoots();
        $run = $this->seedReviewedRun();
        $item = $run->items()->firstOrFail();
        Storage::disk('local')->put($item->source_image_path, 'not-a-webp');

        $this->expectException(CatalogImportPublicationException::class);
        $this->expectExceptionMessage('private image');

        app(CatalogImportPublicationPreflight::class)->inspect($run->fresh());
    }

    public function test_private_image_must_use_the_exact_run_and_item_owned_path(): void
    {
        $this->seedCatalogRoots();
        $run = $this->seedReviewedRun();
        $wrongPath = 'catalog-imports/another-run/images/11889.webp';
        Storage::disk('local')->put($wrongPath, $this->validWebp);
        $run->items()->firstOrFail()->update(['source_image_path' => $wrongPath]);

        $this->expectException(CatalogImportPublicationException::class);
        $this->expectExceptionMessage('private image ownership');

        app(CatalogImportPublicationPreflight::class)->inspect($run->fresh());
    }

    public function test_invalid_product_slug_or_catalog_ownership_collision_is_rejected(): void
    {
        [$categoryId, $subcategoryId] = $this->seedCatalogRoots();
        $run = $this->seedReviewedRun();
        $item = $run->items()->firstOrFail();
        $item->update(['rewritten_slug' => 'belaya-rimskaya-shtora']);

        DB::table('products')->insert([
            'category_id' => $categoryId,
            'subcategory_id' => $subcategoryId,
            'title' => 'Чужой товар',
            'slug' => 'belaya-rimskaya-shtora-11889',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(CatalogImportPublicationException::class);
        $this->expectExceptionMessage('external ID suffix');

        app(CatalogImportPublicationPreflight::class)->inspect($run->fresh());
    }

    public function test_all_items_and_sources_must_be_approved_and_error_free(): void
    {
        $this->seedCatalogRoots();
        $run = $this->seedReviewedRun(['error_count' => 1]);
        $run->items()->firstOrFail()->update([
            'review_status' => CatalogImportItem::STATUS_ERROR,
            'error' => 'rewrite failed',
        ]);

        $this->expectException(CatalogImportPublicationException::class);
        $this->expectExceptionMessage('error-free');

        app(CatalogImportPublicationPreflight::class)->inspect($run->fresh());
    }
}
