<?php

namespace Tests\Feature;

use App\Data\CatalogImport\RewrittenLandingContent;
use App\Data\CatalogImport\RewrittenProductContent;
use App\Models\CatalogAttribute;
use App\Models\CatalogImportItem;
use App\Models\CatalogImportRun;
use App\Models\CatalogImportSource;
use App\Services\CatalogImport\CatalogImportIngestor;
use App\Services\CatalogImport\CatalogImportPackageValidator;
use App\Services\CatalogImport\LandingContentRewriter;
use App\Services\CatalogImport\ProductContentRewriter;
use App\Services\CatalogImport\TemplateProductRewriter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class CatalogImportIngestTest extends TestCase
{
    /** @var array<int, Migration> */
    private array $migrations = [];

    private string $packageDirectory;

    private bool $migrationsAreUp = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createDependencySchema();
        $this->migrations = [
            require database_path('migrations/2026_08_25_000000_create_catalog_import_staging_tables.php'),
            require database_path('migrations/2026_08_25_000100_create_catalog_attribute_tables.php'),
            require database_path('migrations/2026_08_25_000200_add_catalog_import_fields_to_products_and_subcategories.php'),
            require database_path('migrations/2026_08_26_000000_add_catalog_import_image_integrity_fields.php'),
        ];
        foreach ($this->migrations as $migration) {
            $migration->up();
        }
        $this->migrationsAreUp = true;

        Storage::fake('local');
        $this->packageDirectory = storage_path('framework/testing/catalog-import-'.bin2hex(random_bytes(6)));
        File::copyDirectory(base_path('tests/fixtures/catalog-import'), $this->packageDirectory);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->packageDirectory);

        if ($this->migrationsAreUp) {
            foreach (array_reverse($this->migrations) as $migration) {
                $migration->down();
            }
        }
        Schema::dropIfExists('products');
        Schema::dropIfExists('subcategories');
        Schema::dropIfExists('categories');

        parent::tearDown();
    }

    public function test_validator_adapts_the_literal_mixed_case_wire_contract(): void
    {
        $package = $this->validator()->validate($this->manifestPath());

        $this->assertSame('fixture-run-001', $package->runId);
        $this->assertSame(5, $package->requestCount);
        $this->assertSame('0b741805531e882191d13e5f3cbd73c390385948ce29aae27496b63375381976', $package->configDigest);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/D', $package->manifestDigest);
        $this->assertSame(['sources' => 2, 'products' => 1, 'memberships' => 2, 'images' => 1], $package->counts);
        $this->assertSame('rimskie-shtory-belye', $package->sources[0]['target_slug']);
        $this->assertSame('11889', $package->products[0]['external_id']);
        $this->assertSame('2708.00', $package->products[0]['source_price']);
        $this->assertSame('11889', $package->images[0]['external_id']);
        $this->assertSame('rimskie-shtory-belye', $package->memberships[0]['source_slug']);
    }

    public function test_validator_maps_parser_aliases_and_preserves_unknown_attributes(): void
    {
        $this->rewriteManifest(function (array &$manifest): void {
            $manifest['products'][0]['attributes'] = [
                'svetopronitsaemost' => ['50%'],
                'zatemnenie' => ['полупрозрачная'],
                'sostav' => ['100% полиэстер'],
                'plotnost' => ['180 г/м²'],
                'unknown_factory_code' => ['X-12'],
            ];
        });

        $attributes = $this->validator()->validate($this->manifestPath())->products[0]['attributes'];

        $this->assertSame(['50%', 'полупрозрачная'], $attributes['opacity']);
        $this->assertSame(['100% полиэстер'], $attributes['composition']);
        $this->assertSame(['180 г/м²'], $attributes['density']);
        $this->assertSame(['X-12'], $attributes['unknown_factory_code']);
    }

    public function test_validator_recomputes_config_digest_and_requires_state_equality(): void
    {
        $this->rewriteManifest(function (array &$manifest): void {
            $manifest['state']['configDigest'] = str_repeat('0', 64);
        });

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('state.configDigest');

        $this->validator()->validate($this->manifestPath());
    }

    public function test_validator_requires_a_bounded_nonnegative_request_count(): void
    {
        $this->rewriteManifest(function (array &$manifest): void {
            unset($manifest['state']['requestCount']);
        });
        try {
            $this->validator()->validate($this->manifestPath());
            $this->fail('Missing requestCount was accepted.');
        } catch (InvalidArgumentException $error) {
            $this->assertStringContainsString('requestCount', $error->getMessage());
        }

        $this->resetPackage();
        $this->rewriteManifest(function (array &$manifest): void {
            $manifest['state']['requestCount'] = -1;
        });
        try {
            $this->validator()->validate($this->manifestPath());
            $this->fail('Negative requestCount was accepted.');
        } catch (InvalidArgumentException $error) {
            $this->assertStringContainsString('requestCount', $error->getMessage());
        }

        $this->resetPackage();
        $this->rewriteManifest(function (array &$manifest): void {
            $manifest['config']['limits']['max_requests'] = 4;
            $manifest['state']['requestCount'] = 5;
        }, refreshConfigDigest: true);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('requestCount');
        $this->validator()->validate($this->manifestPath());
    }

    public function test_validator_enforces_the_immutable_max_products_cap(): void
    {
        File::copy(
            $this->packageDirectory.'/images/11889.webp',
            $this->packageDirectory.'/images/11890.webp',
        );
        $this->rewriteManifest(function (array &$manifest): void {
            $manifest['config']['limits']['max_products'] = 1;
            $product = $manifest['products'][0];
            $product['externalId'] = '11890';
            $product['sourceUrl'] = 'https://rimskie.com/products/11890-rimskaya-shtora';
            $product['sourceTitle'] = 'Римская штора 11890';
            $product['firstImageUrl'] = 'https://rimskie.com/media/output/11890.webp';
            $product['firstImagePath'] = 'images/11890.webp';
            $manifest['products'][] = $product;
            $image = $manifest['images'][0];
            $image['external_id'] = '11890';
            $image['path'] = 'images/11890.webp';
            $manifest['images'][] = $image;
            $manifest['memberships'][] = [
                'sourceSlug' => 'rimskie-shtory-belye',
                'externalId' => '11890',
            ];
            $manifest['state']['completedProductIds'][] = '11890';
            $manifest['counts']['products'] = 2;
            $manifest['counts']['images'] = 2;
            $manifest['counts']['memberships'] = 3;
        }, refreshConfigDigest: true);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('max_products');
        $this->validator()->validate($this->manifestPath());
    }

    public function test_validator_enforces_price_and_numeric_attribute_decimal_boundaries(): void
    {
        $this->rewriteManifest(function (array &$manifest): void {
            $manifest['products'][0]['sourcePrice'] = '10000000000.00';
        });
        try {
            $this->validator()->validate($this->manifestPath());
            $this->fail('An overflowing DECIMAL(12,2) price was accepted.');
        } catch (InvalidArgumentException $error) {
            $this->assertStringContainsString('sourcePrice', $error->getMessage());
        }

        $this->resetPackage();
        $this->rewriteManifest(function (array &$manifest): void {
            $manifest['products'][0]['sourcePrice'] = '0001.00';
        });
        try {
            $this->validator()->validate($this->manifestPath());
            $this->fail('A non-normalized price was accepted.');
        } catch (InvalidArgumentException $error) {
            $this->assertStringContainsString('sourcePrice', $error->getMessage());
        }

        $this->resetPackage();
        $this->rewriteManifest(function (array &$manifest): void {
            $manifest['products'][0]['attributes']['width'] = ['100000000.0000 мм'];
        });

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('numeric attribute');
        $this->validator()->validate($this->manifestPath());
    }

    public function test_validator_rejects_a_tampered_top_level_config_digest(): void
    {
        $this->rewriteManifest(function (array &$manifest): void {
            $manifest['config_digest'] = str_repeat('f', 64);
            $manifest['state']['configDigest'] = str_repeat('f', 64);
        });

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('config_digest');

        $this->validator()->validate($this->manifestPath());
    }

    public function test_validator_rejects_schema_and_recomputed_count_mismatches(): void
    {
        $this->rewriteManifest(function (array &$manifest): void {
            $manifest['schema_version'] = 'stylish-house.catalog-import/v0';
        });

        try {
            $this->validator()->validate($this->manifestPath());
            $this->fail('Schema mismatch was accepted.');
        } catch (InvalidArgumentException $error) {
            $this->assertStringContainsString('schema_version', $error->getMessage());
        }

        $this->resetPackage();
        $this->rewriteManifest(function (array &$manifest): void {
            $manifest['counts']['memberships'] = 1;
        });

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('counts.memberships');
        $this->validator()->validate($this->manifestPath());
    }

    public function test_validator_rejects_duplicate_source_slugs_and_unapproved_hosts(): void
    {
        $this->rewriteManifest(function (array &$manifest): void {
            $manifest['config']['sources'][1]['sourceSlug'] = $manifest['config']['sources'][0]['sourceSlug'];
        }, refreshConfigDigest: true);

        try {
            $this->validator()->validate($this->manifestPath());
            $this->fail('Duplicate source slug was accepted.');
        } catch (InvalidArgumentException $error) {
            $this->assertStringContainsString('duplicate source slug', $error->getMessage());
        }

        $this->resetPackage();
        $this->rewriteManifest(function (array &$manifest): void {
            $manifest['products'][0]['sourceUrl'] = 'https://example.test/products/11889-rimskaya-shtora';
        });

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('approved rimskie.com');
        $this->validator()->validate($this->manifestPath());
    }

    public function test_validator_rejects_product_url_identity_mismatch(): void
    {
        $this->rewriteManifest(function (array &$manifest): void {
            $manifest['products'][0]['sourceUrl'] = 'https://rimskie.com/products/11890-rimskaya-shtora';
        });

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('product URL ID');

        $this->validator()->validate($this->manifestPath());
    }

    public function test_validator_rejects_literal_dot_segments_in_donor_urls(): void
    {
        $this->rewriteManifest(function (array &$manifest): void {
            $manifest['products'][0]['firstImageUrl'] = 'https://rimskie.com/media/a/../output/11889.webp';
        });

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('ambiguous dot segment');

        $this->validator()->validate($this->manifestPath());
    }

    public function test_validator_rejects_image_traversal_missing_file_and_path_mismatch(): void
    {
        $this->rewriteManifest(function (array &$manifest): void {
            $manifest['products'][0]['firstImagePath'] = '../11889.webp';
            $manifest['images'][0]['path'] = '../11889.webp';
        });

        try {
            $this->validator()->validate($this->manifestPath());
            $this->fail('Image traversal was accepted.');
        } catch (InvalidArgumentException $error) {
            $this->assertStringContainsString('images/11889.webp', $error->getMessage());
        }

        $this->resetPackage();
        File::delete($this->packageDirectory.'/images/11889.webp');
        try {
            $this->validator()->validate($this->manifestPath());
            $this->fail('Missing image was accepted.');
        } catch (InvalidArgumentException $error) {
            $this->assertStringContainsString('regular image file', $error->getMessage());
        }

        $this->resetPackage();
        $this->rewriteManifest(function (array &$manifest): void {
            $manifest['images'][0]['path'] = 'images/11890.webp';
        });

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('image path');
        $this->validator()->validate($this->manifestPath());
    }

    public function test_validator_rejects_image_length_hash_and_webp_structure_mismatches(): void
    {
        $this->rewriteManifest(function (array &$manifest): void {
            $manifest['images'][0]['byte_length'] = 31;
        });
        try {
            $this->validator()->validate($this->manifestPath());
            $this->fail('Wrong byte length was accepted.');
        } catch (InvalidArgumentException $error) {
            $this->assertStringContainsString('byte length', $error->getMessage());
        }

        $this->resetPackage();
        $this->rewriteManifest(function (array &$manifest): void {
            $manifest['images'][0]['sha256'] = str_repeat('0', 64);
        });
        try {
            $this->validator()->validate($this->manifestPath());
            $this->fail('Wrong SHA-256 was accepted.');
        } catch (InvalidArgumentException $error) {
            $this->assertStringContainsString('SHA-256', $error->getMessage());
        }

        $this->resetPackage();
        File::put($this->packageDirectory.'/images/11889.webp', str_repeat('x', 30));
        $this->rewriteManifest(function (array &$manifest): void {
            $manifest['images'][0]['sha256'] = hash('sha256', str_repeat('x', 30));
        });

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('WebP');
        $this->validator()->validate($this->manifestPath());
    }

    public function test_validator_rejects_text_that_exceeds_database_string_boundaries(): void
    {
        $this->rewriteManifest(function (array &$manifest): void {
            $manifest['products'][0]['sourceTitle'] = str_repeat('я', 256);
        });
        try {
            $this->validator()->validate($this->manifestPath());
            $this->fail('A source title longer than the database column was accepted.');
        } catch (InvalidArgumentException $error) {
            $this->assertStringContainsString('sourceTitle', $error->getMessage());
        }

        $this->resetPackage();
        $this->rewriteManifest(function (array &$manifest): void {
            $manifest['products'][0]['attributes']['color'] = [str_repeat('я', 256)];
        });

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('attribute value');
        $this->validator()->validate($this->manifestPath());
    }

    public function test_long_database_safe_input_ingests_with_capped_public_copy(): void
    {
        $this->rewriteManifest(function (array &$manifest): void {
            $manifest['run_id'] = 'fixture-run-long-copy';
            $manifest['products'][0]['sourceTitle'] = rtrim(mb_substr(
                str_repeat('Длинная римская штора ', 12),
                0,
                240,
            ));
            $manifest['products'][0]['sourceDescription'] = str_repeat('Факты о модели. ', 200);
            $manifest['products'][0]['attributes'] = [
                'material' => [rtrim(mb_substr(str_repeat('материал ', 30), 0, 250))],
                'color' => [rtrim(mb_substr(str_repeat('цвет ', 50), 0, 250))],
                'style' => [rtrim(mb_substr(str_repeat('стиль ', 50), 0, 250))],
            ];
        });

        $package = $this->validator()->validate($this->manifestPath());
        $run = $this->ingestor()->ingest($package);
        $item = $run->items()->firstOrFail();

        $this->assertSame(240, mb_strlen($item->source_title));
        $this->assertLessThanOrEqual(140, mb_strlen($item->rewritten_title));
        $this->assertLessThanOrEqual(220, mb_strlen($item->rewritten_summary));
        $this->assertLessThanOrEqual(1000, mb_strlen($item->rewritten_description));
        $this->assertContains('title_truncated', $item->warnings);
        $this->assertContains('summary_truncated', $item->warnings);
        $this->assertContains('description_truncated', $item->warnings);
    }

    public function test_ingest_is_idempotent_and_preserves_manual_review_changes(): void
    {
        $package = $this->validator()->validate($this->manifestPath());
        $first = $this->ingestor()->ingest($package);
        $item = $first->items()->firstOrFail();
        $source = $first->sources()->orderBy('sort_order')->firstOrFail();
        $item->update([
            'rewritten_title' => 'Ручная редактура товара',
            'review_status' => CatalogImportItem::STATUS_APPROVED,
            'review_notes' => 'Проверено вручную',
        ]);
        $source->update([
            'rewritten_title' => 'Ручная редактура посадочной',
            'review_status' => CatalogImportSource::REVIEW_REJECTED,
            'review_notes' => 'Нужна доработка',
        ]);
        $first->update(['status' => CatalogImportRun::STATUS_REVIEWING]);

        $second = $this->ingestor()->ingest($this->validator()->validate($this->manifestPath()));

        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('catalog_import_runs', 1);
        $this->assertDatabaseCount('catalog_import_sources', 2);
        $this->assertDatabaseCount('catalog_import_items', 1);
        $this->assertDatabaseCount('catalog_import_item_source', 2);
        $this->assertDatabaseCount('catalog_import_item_attribute_value', 2);
        $this->assertCount(1, Storage::disk('local')->allFiles('catalog-imports/fixture-run-001/images'));
        $this->assertSame('Ручная редактура товара', $item->fresh()->rewritten_title);
        $this->assertSame(CatalogImportItem::STATUS_APPROVED, $item->fresh()->review_status);
        $this->assertSame('Проверено вручную', $item->fresh()->review_notes);
        $this->assertSame('Ручная редактура посадочной', $source->fresh()->rewritten_title);
        $this->assertSame(CatalogImportSource::REVIEW_REJECTED, $source->fresh()->review_status);
        $this->assertSame('Нужна доработка', $source->fresh()->review_notes);
        $this->assertSame(CatalogImportRun::STATUS_REVIEWING, $second->fresh()->status);
        $this->assertSame('2708.00', $item->fresh()->source_price);
        $this->assertSame('4a5d3458dfeabd63090fe08fdc8224ae61d45f0c430eee3c0544a92891af3b69', $item->fresh()->source_image_sha256);
        $this->assertSame(30, $item->fresh()->source_image_byte_length);
        $this->assertStringEndsWith('-11889', $item->fresh()->rewritten_slug);
        $this->assertSame($package->manifestDigest, $second->config['manifest_digest']);
        $this->assertSame($package->configDigest, $second->config['config_digest']);
        $this->assertSame(5, $second->config['collector_request_count']);
    }

    public function test_verified_identical_no_op_does_not_invoke_rewriters(): void
    {
        $package = $this->validator()->validate($this->manifestPath());
        $run = $this->ingestor()->ingest($package);
        $throwingProduct = new class implements ProductContentRewriter
        {
            public function rewrite(array $source): RewrittenProductContent
            {
                throw new RuntimeException('Product rewriter must not run for a verified no-op.');
            }
        };
        $throwingLanding = new class implements LandingContentRewriter
        {
            public function rewrite(string $label, string $targetSlug): RewrittenLandingContent
            {
                throw new RuntimeException('Landing rewriter must not run for a verified no-op.');
            }
        };

        $repeated = (new CatalogImportIngestor($throwingProduct, $throwingLanding))->ingest($package);

        $this->assertSame($run->id, $repeated->id);
    }

    public function test_existing_run_rejects_a_changed_config_and_manifest_digest(): void
    {
        $this->ingestor()->ingest($this->validator()->validate($this->manifestPath()));
        $this->rewriteManifest(function (array &$manifest): void {
            $manifest['config']['limits']['max_products'] = 1;
        }, refreshConfigDigest: true);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('digest');

        $this->ingestor()->ingest($this->validator()->validate($this->manifestPath()));
    }

    public function test_repeat_ingest_rejects_tampered_immutable_staging_identity(): void
    {
        $package = $this->validator()->validate($this->manifestPath());
        $run = $this->ingestor()->ingest($package);
        $run->sources()->orderBy('sort_order')->firstOrFail()->update([
            'target_slug' => 'tampered-source-slug',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('immutable source');

        $this->ingestor()->ingest($package);
    }

    public function test_repeat_ingest_rejects_a_same_count_cross_run_membership(): void
    {
        $package = $this->validator()->validate($this->manifestPath());
        $run = $this->ingestor()->ingest($package);
        $item = $run->items()->firstOrFail();
        $replacedSource = $run->sources()->orderBy('sort_order')->firstOrFail();
        $otherRun = CatalogImportRun::create([
            'provider' => 'rimskie.com',
            'external_run_id' => 'other-run',
            'status' => CatalogImportRun::STATUS_STAGED,
            'config' => [],
        ]);
        $foreignSource = $otherRun->sources()->create([
            'label' => $replacedSource->label,
            'source_url' => $replacedSource->source_url,
            'target_slug' => $replacedSource->target_slug,
            'enabled' => true,
            'status' => CatalogImportSource::STATUS_COMPLETED,
            'sort_order' => 1,
            'pages_count' => 1,
            'items_count' => 1,
            'review_status' => CatalogImportSource::REVIEW_NEEDS_REVIEW,
        ]);
        DB::table('catalog_import_item_source')
            ->where('import_item_id', $item->id)
            ->where('import_source_id', $replacedSource->id)
            ->delete();
        DB::table('catalog_import_item_source')->insert([
            'import_item_id' => $item->id,
            'import_source_id' => $foreignSource->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->assertSame(2, DB::table('catalog_import_item_source')
            ->where('import_item_id', $item->id)->count());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('membership set');
        $this->ingestor()->ingest($package);
    }

    public function test_repeat_ingest_rejects_a_changed_imported_attribute_set(): void
    {
        $package = $this->validator()->validate($this->manifestPath());
        $run = $this->ingestor()->ingest($package);
        $item = $run->items()->firstOrFail();
        $attributeValueId = DB::table('catalog_import_item_attribute_value')
            ->where('import_item_id', $item->id)
            ->value('attribute_value_id');
        DB::table('catalog_import_item_attribute_value')
            ->where('import_item_id', $item->id)
            ->where('attribute_value_id', $attributeValueId)
            ->delete();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('attribute set');
        $this->ingestor()->ingest($package);
    }

    public function test_existing_conflicting_private_image_is_never_overwritten(): void
    {
        Storage::disk('local')->put('catalog-imports/fixture-run-001/images/11889.webp', 'conflict');

        try {
            $this->ingestor()->ingest($this->validator()->validate($this->manifestPath()));
            $this->fail('Conflicting destination bytes were overwritten.');
        } catch (RuntimeException $error) {
            $this->assertStringContainsString('conflicting image', $error->getMessage());
        }

        $this->assertSame('conflict', Storage::disk('local')->get('catalog-imports/fixture-run-001/images/11889.webp'));
        $this->assertDatabaseCount('catalog_import_runs', 0);
    }

    public function test_private_image_copy_rejects_a_symlinked_run_directory_ancestor(): void
    {
        $disk = Storage::disk('local');
        $disk->makeDirectory('catalog-imports');
        $outside = storage_path('framework/testing/catalog-import-outside-'.bin2hex(random_bytes(4)));
        File::makeDirectory($outside, 0755, true);
        $link = $disk->path('catalog-imports/fixture-run-001');
        if (! @symlink($outside, $link)) {
            File::deleteDirectory($outside);
            $this->markTestSkipped('Directory symlinks are unavailable on this host.');
        }

        try {
            $this->ingestor()->ingest($this->validator()->validate($this->manifestPath()));
            $this->fail('A symlinked private run directory was accepted.');
        } catch (RuntimeException $error) {
            $this->assertStringContainsString('private destination', mb_strtolower($error->getMessage()));
        } finally {
            @unlink($link);
            File::deleteDirectory($outside);
        }

        $this->assertDatabaseCount('catalog_import_runs', 0);
    }

    public function test_private_image_copy_rejects_a_windows_junction_run_directory_ancestor(): void
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            $this->markTestSkipped('Windows junction regression.');
        }

        $disk = Storage::disk('local');
        $disk->makeDirectory('catalog-imports');
        $outside = storage_path('framework/testing/catalog-import-junction-target-'.bin2hex(random_bytes(4)));
        File::makeDirectory($outside, 0755, true);
        $sentinel = $outside.DIRECTORY_SEPARATOR.'sentinel.txt';
        File::put($sentinel, 'unchanged');
        $outsideBefore = [basename($sentinel) => hash_file('sha256', $sentinel)];
        $junction = $disk->path('catalog-imports/fixture-run-001');
        $process = new Process([
            'powershell.exe',
            '-NoProfile',
            '-NonInteractive',
            '-Command',
            '& { param($linkPath, $targetPath) '
                .'New-Item -ItemType Junction -Path $linkPath -Target $targetPath | Out-Null }',
            $junction,
            $outside,
        ]);
        $process->run();
        $this->assertTrue($process->isSuccessful(), $process->getErrorOutput().$process->getOutput());

        try {
            $this->ingestor()->ingest($this->validator()->validate($this->manifestPath()));
            $this->fail('A junction private run directory was accepted.');
        } catch (RuntimeException $error) {
            $this->assertStringContainsString('private destination', mb_strtolower($error->getMessage()));
        } finally {
            $outsideAfter = collect(File::files($outside))
                ->mapWithKeys(fn (\SplFileInfo $file): array => [
                    $file->getFilename() => hash_file('sha256', $file->getPathname()),
                ])
                ->all();
            $this->assertSame($outsideBefore, $outsideAfter, 'Rejected junction must not mutate its target.');
            @rmdir($junction);
            File::deleteDirectory($outside);
        }

        $this->assertDatabaseCount('catalog_import_runs', 0);
    }

    public function test_repeat_ingest_rejects_a_windows_junction_substituted_after_commit(): void
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            $this->markTestSkipped('Windows junction regression.');
        }

        $package = $this->validator()->validate($this->manifestPath());
        $this->ingestor()->ingest($package);
        $disk = Storage::disk('local');
        $runDirectory = $disk->path('catalog-imports/fixture-run-001');
        $savedDirectory = $disk->path('catalog-imports/fixture-run-001-saved');
        $outside = storage_path('framework/testing/catalog-import-junction-verified-'.bin2hex(random_bytes(4)));
        File::makeDirectory($outside.DIRECTORY_SEPARATOR.'images', 0755, true);
        File::copy(
            $runDirectory.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'11889.webp',
            $outside.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'11889.webp',
        );
        File::moveDirectory($runDirectory, $savedDirectory);
        $process = new Process([
            'powershell.exe',
            '-NoProfile',
            '-NonInteractive',
            '-Command',
            '& { param($linkPath, $targetPath) '
                .'New-Item -ItemType Junction -Path $linkPath -Target $targetPath | Out-Null }',
            $runDirectory,
            $outside,
        ]);
        $process->run();
        $this->assertTrue($process->isSuccessful(), $process->getErrorOutput().$process->getOutput());

        try {
            $this->ingestor()->ingest($package);
            $this->fail('A substituted junction was accepted during verified no-op.');
        } catch (RuntimeException $error) {
            $this->assertStringContainsString('private destination', mb_strtolower($error->getMessage()));
        } finally {
            @rmdir($runDirectory);
            File::moveDirectory($savedDirectory, $runDirectory);
            File::deleteDirectory($outside);
        }
    }

    public function test_repeat_ingest_rejects_a_missing_or_corrupt_committed_private_image(): void
    {
        $package = $this->validator()->validate($this->manifestPath());
        $this->ingestor()->ingest($package);
        $path = 'catalog-imports/fixture-run-001/images/11889.webp';

        Storage::disk('local')->delete($path);
        try {
            $this->ingestor()->ingest($package);
            $this->fail('Missing committed image was accepted as an idempotent run.');
        } catch (RuntimeException $error) {
            $this->assertStringContainsString('conflicting image', $error->getMessage());
        }

        Storage::disk('local')->put($path, 'corrupt');
        try {
            $this->ingestor()->ingest($package);
            $this->fail('Corrupt committed image was accepted as an idempotent run.');
        } catch (RuntimeException $error) {
            $this->assertStringContainsString('conflicting image', $error->getMessage());
        }

        $this->assertDatabaseCount('catalog_import_runs', 1);
        $this->assertDatabaseCount('catalog_import_items', 1);
    }

    public function test_new_private_files_are_removed_when_the_database_transaction_fails(): void
    {
        Schema::drop('catalog_import_item_source');

        try {
            $this->ingestor()->ingest($this->validator()->validate($this->manifestPath()));
            $this->fail('Broken transaction unexpectedly completed.');
        } catch (\Throwable) {
            $this->assertFalse(Storage::disk('local')->exists('catalog-imports/fixture-run-001/images/11889.webp'));
            $this->assertDatabaseCount('catalog_import_runs', 0);
        } finally {
            Schema::create('catalog_import_item_source', function (Blueprint $table): void {
                $table->unsignedBigInteger('import_item_id');
                $table->unsignedBigInteger('import_source_id');
                $table->timestamps();
                $table->unique(['import_item_id', 'import_source_id'], 'ciis_item_source_uq');
            });
        }
    }

    public function test_uncertain_commit_check_preserves_image_and_requires_manual_verification(): void
    {
        Schema::drop('catalog_import_item_source');
        $ingestor = new class extends CatalogImportIngestor
        {
            protected function committedRunExists(\App\Data\CatalogImport\ValidatedCatalogImportPackage $package): bool
            {
                throw new RuntimeException('simulated database check failure');
            }
        };

        try {
            $ingestor->ingest($this->validator()->validate($this->manifestPath()));
            $this->fail('An uncertain commit state was hidden.');
        } catch (RuntimeException $error) {
            $this->assertStringContainsString('manual verification', mb_strtolower($error->getMessage()));
            $this->assertTrue(Storage::disk('local')->exists(
                'catalog-imports/fixture-run-001/images/11889.webp'
            ));
        } finally {
            $this->recreateMembershipSchema();
        }
    }

    public function test_failed_compensation_delete_is_reported_and_not_silent(): void
    {
        Schema::drop('catalog_import_item_source');
        $ingestor = new class extends CatalogImportIngestor
        {
            protected function deleteCompensationFile(FilesystemAdapter $disk, string $relativePath): bool
            {
                return false;
            }
        };

        try {
            $ingestor->ingest($this->validator()->validate($this->manifestPath()));
            $this->fail('A failed compensation delete was hidden.');
        } catch (RuntimeException $error) {
            $this->assertStringContainsString('manual verification', mb_strtolower($error->getMessage()));
            $this->assertTrue(Storage::disk('local')->exists(
                'catalog-imports/fixture-run-001/images/11889.webp'
            ));
        } finally {
            $this->recreateMembershipSchema();
        }
    }

    public function test_compensation_rejects_a_windows_junction_substituted_before_delete(): void
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            $this->markTestSkipped('Windows junction regression.');
        }

        Schema::drop('catalog_import_item_source');
        $disk = Storage::disk('local');
        $runDirectory = $disk->path('catalog-imports/fixture-run-001');
        $savedDirectory = $disk->path('catalog-imports/fixture-run-001-before-cleanup');
        $outside = storage_path('framework/testing/catalog-import-cleanup-junction-'.bin2hex(random_bytes(4)));
        File::makeDirectory($outside.DIRECTORY_SEPARATOR.'images', 0755, true);
        $sentinel = $outside.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'11889.webp';
        File::put($sentinel, 'outside-file-must-not-be-deleted');
        $sentinelHash = hash_file('sha256', $sentinel);
        $ingestor = new class($runDirectory, $savedDirectory, $outside) extends CatalogImportIngestor
        {
            public function __construct(
                private readonly string $runDirectory,
                private readonly string $savedDirectory,
                private readonly string $outside,
            ) {
                parent::__construct();
            }

            protected function committedRunExists(
                \App\Data\CatalogImport\ValidatedCatalogImportPackage $package,
            ): bool {
                File::moveDirectory($this->runDirectory, $this->savedDirectory);
                $process = new Process([
                    'powershell.exe',
                    '-NoProfile',
                    '-NonInteractive',
                    '-Command',
                    '& { param($linkPath, $targetPath) '
                        .'New-Item -ItemType Junction -Path $linkPath -Target $targetPath | Out-Null }',
                    $this->runDirectory,
                    $this->outside,
                ]);
                $process->mustRun();

                return false;
            }
        };

        try {
            $ingestor->ingest($this->validator()->validate($this->manifestPath()));
            $this->fail('A substituted cleanup junction was not reported.');
        } catch (RuntimeException $error) {
            $this->assertStringContainsString('manual verification', mb_strtolower($error->getMessage()));
            $this->assertFileExists($sentinel);
            $this->assertSame($sentinelHash, hash_file('sha256', $sentinel));
            $this->assertFileExists($savedDirectory.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'11889.webp');
        } finally {
            @rmdir($runDirectory);
            File::deleteDirectory($savedDirectory);
            File::deleteDirectory($outside);
            $this->recreateMembershipSchema();
        }
    }

    public function test_aliases_become_known_filters_and_unknown_attributes_stay_non_public(): void
    {
        $this->rewriteManifest(function (array &$manifest): void {
            $manifest['run_id'] = 'fixture-run-aliases';
            $manifest['products'][0]['attributes'] = [
                'svetopronitsaemost' => ['50%'],
                'sostav' => ['100% полиэстер'],
                'plotnost' => ['180 г/м²'],
                'unknown_factory_code' => ['X-12'],
            ];
        });

        $run = $this->ingestor()->ingest($this->validator()->validate($this->manifestPath()));
        $unknown = CatalogAttribute::where('code', 'unknown_factory_code')->firstOrFail();

        $this->assertFalse($unknown->is_public);
        $this->assertTrue(CatalogAttribute::where('code', 'opacity')->firstOrFail()->is_public);
        $this->assertTrue(CatalogAttribute::where('code', 'composition')->firstOrFail()->is_public);
        $this->assertTrue(CatalogAttribute::where('code', 'density')->firstOrFail()->is_public);
        $warnings = $run->items()->firstOrFail()->warnings;
        $this->assertContains('unknown_attribute:unknown_factory_code', $warnings);
        $sorted = $warnings;
        sort($sorted, SORT_STRING);
        $this->assertSame($sorted, $warnings);
    }

    public function test_unknown_attribute_collision_never_mutates_existing_public_filter_metadata(): void
    {
        $existing = CatalogAttribute::create([
            'code' => 'unknown_factory_code',
            'label' => 'Existing public filter',
            'type' => CatalogAttribute::TYPE_SELECT,
            'sort_order' => 99,
            'is_public' => true,
        ]);
        $this->rewriteManifest(function (array &$manifest): void {
            $manifest['run_id'] = 'fixture-run-collision';
            $manifest['products'][0]['attributes']['unknown_factory_code'] = ['X-12'];
        });

        try {
            $this->ingestor()->ingest($this->validator()->validate($this->manifestPath()));
            $this->fail('Unknown attribute reused a pre-existing public filter.');
        } catch (RuntimeException $error) {
            $this->assertStringContainsString('attribute metadata collision', $error->getMessage());
        }

        $this->assertTrue($existing->fresh()->is_public);
        $this->assertSame('Existing public filter', $existing->fresh()->label);
        $this->assertDatabaseCount('catalog_import_runs', 0);
        $this->assertFalse(Storage::disk('local')->exists(
            'catalog-imports/fixture-run-collision/images/11889.webp'
        ));
    }

    public function test_existing_known_filter_with_wrong_visibility_is_rejected(): void
    {
        CatalogAttribute::create([
            'code' => 'color',
            'label' => 'цвет',
            'type' => CatalogAttribute::TYPE_SELECT,
            'sort_order' => 1,
            'is_public' => false,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('attribute metadata collision');
        $this->ingestor()->ingest($this->validator()->validate($this->manifestPath()));
    }

    public function test_existing_normalized_filter_value_with_wrong_label_is_rejected(): void
    {
        $attribute = CatalogAttribute::create([
            'code' => 'color',
            'label' => 'цвет',
            'type' => CatalogAttribute::TYPE_SELECT,
            'sort_order' => 1,
            'is_public' => true,
        ]);
        $attribute->values()->create([
            'normalized_value' => 'belyi',
            'label' => 'Чужое значение',
            'numeric_value' => null,
            'sort_order' => 1,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('attribute value metadata collision');
        $this->ingestor()->ingest($this->validator()->validate($this->manifestPath()));
    }

    public function test_existing_numeric_filter_value_with_wrong_numeric_metadata_is_rejected(): void
    {
        $this->rewriteManifest(function (array &$manifest): void {
            $manifest['products'][0]['attributes']['width'] = ['160 мм'];
        });
        $attribute = CatalogAttribute::create([
            'code' => 'width',
            'label' => 'ширина',
            'type' => CatalogAttribute::TYPE_NUMBER,
            'sort_order' => 1,
            'is_public' => true,
        ]);
        $attribute->values()->create([
            'normalized_value' => '160-mm',
            'label' => '160 мм',
            'numeric_value' => '161.0000',
            'sort_order' => 1,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('attribute value metadata collision');
        $this->ingestor()->ingest($this->validator()->validate($this->manifestPath()));
    }

    public function test_repeat_ingest_rejects_flipped_imported_filter_metadata(): void
    {
        $package = $this->validator()->validate($this->manifestPath());
        $this->ingestor()->ingest($package);
        CatalogAttribute::where('code', 'color')->firstOrFail()->update(['is_public' => false]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('attribute metadata');
        $this->ingestor()->ingest($package);
    }

    public function test_hostile_value_under_known_code_is_staged_only_as_non_public(): void
    {
        $this->rewriteManifest(function (array &$manifest): void {
            $manifest['run_id'] = 'fixture-run-hostile-attribute';
            $manifest['products'][0]['attributes'] = [
                'color' => ['скидка 90% +7 (999) 123‑45‑67 цена 2708 ₽'],
            ];
        });

        $run = $this->ingestor()->ingest($this->validator()->validate($this->manifestPath()));
        $attribute = CatalogAttribute::where('code', 'color')->firstOrFail();

        $this->assertFalse($attribute->is_public);
        $this->assertSame(1, $attribute->values()->count());
        $warnings = $run->items()->firstOrFail()->warnings;
        $this->assertContains('removed_contact', $warnings);
        $this->assertContains('removed_price', $warnings);
        $this->assertContains('removed_promotional', $warnings);
    }

    public function test_double_encoded_markup_attribute_value_never_becomes_a_public_filter(): void
    {
        $deepMarkup = $this->encodeRepeatedly('<script>alert(1)</script>', 6);
        $this->rewriteManifest(function (array &$manifest) use ($deepMarkup): void {
            $manifest['run_id'] = 'fixture-run-encoded-attribute';
            $manifest['products'][0]['attributes'] = [
                'color' => [$deepMarkup.' KOR TIN +7.999.123.45.67 экологичный №1'],
            ];
        });

        $run = $this->ingestor()->ingest($this->validator()->validate($this->manifestPath()));

        $this->assertFalse(CatalogAttribute::where('code', 'color')->firstOrFail()->is_public);
        $warnings = $run->items()->firstOrFail()->warnings;
        $this->assertContains('removed_markup', $warnings);
        $this->assertContains('removed_branding', $warnings);
        $this->assertContains('removed_contact', $warnings);
        $this->assertContains('removed_promotional', $warnings);
    }

    public function test_duplicate_landing_copy_flags_every_colliding_source_without_changing_copy(): void
    {
        $duplicateLanding = new class implements LandingContentRewriter
        {
            public function rewrite(string $label, string $targetSlug): RewrittenLandingContent
            {
                return new RewrittenLandingContent(
                    title: 'Одинаковый title',
                    h1: 'Одинаковый H1',
                    intro: 'Одинаковый intro',
                    description: 'Одинаковое description',
                    seo: 'Одинаковый SEO',
                    warnings: [],
                );
            }
        };
        $ingestor = new CatalogImportIngestor(new TemplateProductRewriter, $duplicateLanding);

        $run = $ingestor->ingest($this->validator()->validate($this->manifestPath()));
        $sources = $run->sources()->orderBy('sort_order')->get();

        $this->assertCount(2, $sources);
        foreach ($sources as $source) {
            $this->assertContains('duplicate_landing_copy', $source->warnings);
            $this->assertSame('Одинаковый title', $source->rewritten_title);
            $this->assertSame('Одинаковый H1', $source->rewritten_h1);
        }
    }

    public function test_dry_run_reports_recomputed_counts_without_mutating_database_or_storage(): void
    {
        CatalogAttribute::create([
            'code' => 'sentinel',
            'label' => 'Sentinel',
            'type' => CatalogAttribute::TYPE_SELECT,
            'sort_order' => 1,
            'is_public' => false,
        ]);
        Storage::disk('local')->put('sentinel.bin', "unchanged\0bytes");
        $databaseBefore = $this->databaseSnapshot();
        $filesBefore = $this->storageSnapshot();
        $ingestor = $this->createMock(CatalogImportIngestor::class);
        $ingestor->expects($this->never())->method('ingest');
        $this->app->instance(CatalogImportIngestor::class, $ingestor);

        $this->artisan('catalog-import:ingest', [
            'manifest' => $this->manifestPath(),
            '--dry-run' => true,
        ])
            ->expectsOutputToContain('sources=2 products=1 memberships=2 images=1')
            ->assertSuccessful();

        $this->assertSame($databaseBefore, $this->databaseSnapshot());
        $this->assertSame($filesBefore, $this->storageSnapshot());
    }

    public function test_dry_run_reports_the_same_real_rewrite_warning_count_as_ingest(): void
    {
        $exitCode = Artisan::call('catalog-import:ingest', [
            'manifest' => $this->manifestPath(),
            '--dry-run' => true,
        ]);
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertMatchesRegularExpression('/warnings=([1-9]\d*)/', $output);
        preg_match('/warnings=(\d+)/', $output, $matches);
        $dryRunWarnings = (int) $matches[1];

        $run = $this->ingestor()->ingest($this->validator()->validate($this->manifestPath()));
        $storedWarnings = $run->sources->sum(
            static fn (CatalogImportSource $source): int => count($source->warnings ?? []),
        ) + $run->items->sum(
            static fn (CatalogImportItem $item): int => count($item->warnings ?? []),
        );

        $this->assertSame($storedWarnings, $dryRunWarnings);
    }

    public function test_dry_run_exercises_landing_rewrite_validation_without_writes(): void
    {
        $this->rewriteManifest(function (array &$manifest): void {
            $manifest['config']['sources'][0]['sourceSlug'] = 'foo--bar';
            $manifest['state']['sources'][0]['sourceSlug'] = 'foo--bar';
            $manifest['sources'][0]['target_slug'] = 'foo--bar';
            $manifest['memberships'][0]['sourceSlug'] = 'foo--bar';
        }, refreshConfigDigest: true);

        $exitCode = Artisan::call('catalog-import:ingest', [
            'manifest' => $this->manifestPath(),
            '--dry-run' => true,
        ]);

        $this->assertSame(1, $exitCode);
        $this->assertDatabaseCount('catalog_import_runs', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_command_redacts_arbitrary_ingest_exception_messages(): void
    {
        $secret = 'SECRET_BINDING_MARKER_7f4d';
        $failingIngestor = new class($secret) extends CatalogImportIngestor
        {
            public function __construct(private readonly string $secret) {}

            public function ingest(\App\Data\CatalogImport\ValidatedCatalogImportPackage $package): CatalogImportRun
            {
                throw new RuntimeException('SQLSTATE binding '.$this->secret);
            }
        };
        $this->app->instance(CatalogImportIngestor::class, $failingIngestor);

        $exitCode = Artisan::call('catalog-import:ingest', ['manifest' => $this->manifestPath()]);
        $output = Artisan::output();

        $this->assertSame(1, $exitCode);
        $this->assertStringNotContainsString($secret, $output);
        $this->assertStringContainsString('Catalog import failed safely', $output);
    }

    private function validator(): CatalogImportPackageValidator
    {
        return app(CatalogImportPackageValidator::class);
    }

    private function ingestor(): CatalogImportIngestor
    {
        return app(CatalogImportIngestor::class);
    }

    private function manifestPath(): string
    {
        return $this->packageDirectory.'/export.json';
    }

    private function resetPackage(): void
    {
        File::deleteDirectory($this->packageDirectory);
        File::copyDirectory(base_path('tests/fixtures/catalog-import'), $this->packageDirectory);
    }

    private function rewriteManifest(callable $mutate, bool $refreshConfigDigest = false): void
    {
        $manifest = json_decode(File::get($this->manifestPath()), true, flags: JSON_THROW_ON_ERROR);
        $mutate($manifest);
        if ($refreshConfigDigest) {
            $digest = hash('sha256', json_encode(
                $this->canonicalize($manifest['config']),
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
            ));
            $manifest['config_digest'] = $digest;
            $manifest['state']['configDigest'] = $digest;
        }
        File::put($this->manifestPath(), json_encode(
            $manifest,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        )."\n");
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

    private function encodeRepeatedly(string $value, int $passes): string
    {
        for ($pass = 0; $pass < $passes; $pass++) {
            $value = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        return $value;
    }

    /** @return array<string, array<int, array<string, mixed>>> */
    private function databaseSnapshot(): array
    {
        $tables = [
            'catalog_import_runs',
            'catalog_import_sources',
            'catalog_import_items',
            'catalog_import_item_source',
            'catalog_attributes',
            'catalog_attribute_values',
            'catalog_import_item_attribute_value',
        ];
        $snapshot = [];
        foreach ($tables as $table) {
            $snapshot[$table] = DB::table($table)->get()
                ->map(static fn (object $row): array => (array) $row)
                ->all();
        }

        return $snapshot;
    }

    /** @return array<string, string> */
    private function storageSnapshot(): array
    {
        $snapshot = [];
        foreach (Storage::disk('local')->allFiles() as $path) {
            $snapshot[$path] = hash('sha256', Storage::disk('local')->get($path));
        }
        ksort($snapshot, SORT_STRING);

        return $snapshot;
    }

    private function createDependencySchema(): void
    {
        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->timestamps();
        });
        Schema::create('subcategories', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('title');
            $table->string('slug')->unique();
            $table->timestamps();
        });
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('subcategory_id')->nullable();
            $table->string('title');
            $table->string('slug')->unique();
            $table->timestamps();
        });
    }

    private function recreateMembershipSchema(): void
    {
        Schema::create('catalog_import_item_source', function (Blueprint $table): void {
            $table->unsignedBigInteger('import_item_id');
            $table->unsignedBigInteger('import_source_id');
            $table->timestamps();
            $table->unique(['import_item_id', 'import_source_id'], 'ciis_item_source_uq');
        });
    }
}
