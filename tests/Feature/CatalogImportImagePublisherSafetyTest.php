<?php

namespace Tests\Feature;

use App\Data\CatalogImport\PublishedCatalogImportImage;
use App\Data\CatalogImport\QuarantinedCatalogImportImage;
use App\Models\CatalogImportRun;
use App\Services\CatalogImport\Publication\CatalogImportImagePublisher;
use App\Services\CatalogImport\Publication\CatalogImportPublicationException;
use App\Services\CatalogImport\Publication\CatalogImportPublicationPreflight;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CatalogImportPublicationTestCase;

class CatalogImportImagePublisherSafetyTest extends CatalogImportPublicationTestCase
{
    public function test_public_creation_rejects_junction_before_writing_through_it(): void
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            $this->markTestSkipped('This regression covers Windows junction traversal.');
        }
        $this->seedCatalogRoots();
        $run = $this->seedReviewedRun();
        $disk = Storage::disk('public');
        $targetDirectory = $disk->path('junction-target');
        $junctionDirectory = $disk->path('catalog-imports');
        mkdir($targetDirectory, 0700, true);
        file_put_contents($targetDirectory.DIRECTORY_SEPARATOR.'sentinel.txt', 'must-remain');
        if (! $this->createJunction($junctionDirectory, $targetDirectory)) {
            $this->markTestSkipped('This host does not permit creating a test junction.');
        }

        try {
            app(CatalogImportPublicationPreflight::class)->inspect($run);
            $this->fail('A public destination below a junction must fail preflight.');
        } catch (CatalogImportPublicationException $exception) {
            $this->assertStringContainsString('junction', $exception->getMessage());
            $this->assertFileDoesNotExist(
                $targetDirectory.DIRECTORY_SEPARATOR.'full-run-001'.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'11889.webp'
            );
            $this->assertSame(
                'must-remain',
                file_get_contents($targetDirectory.DIRECTORY_SEPARATOR.'sentinel.txt'),
            );
        } finally {
            @rmdir($junctionDirectory);
        }
    }

    public function test_compensation_refuses_to_silently_delete_changed_evidence(): void
    {
        $relativePath = 'catalog-imports/full-run-001/images/11889.webp';
        Storage::disk('public')->put($relativePath, 'changed-after-publication');
        $image = new PublishedCatalogImportImage(
            relativePath: $relativePath,
            databasePath: 'storage/'.$relativePath,
            sha256: hash('sha256', $this->validWebp),
            byteLength: strlen($this->validWebp),
            created: true,
        );

        $this->expectException(CatalogImportPublicationException::class);
        try {
            (new CatalogImportImagePublisher)->compensate([$image]);
        } finally {
            $this->assertSame('changed-after-publication', Storage::disk('public')->get($relativePath));
        }
    }

    public function test_compensation_refuses_same_bytes_replacement_with_different_file_identity(): void
    {
        $this->seedCatalogRoots();
        $run = $this->seedReviewedRun();
        $publisher = new CatalogImportImagePublisher;
        $image = $publisher->publish($run, $run->items()->firstOrFail());
        $path = Storage::disk('public')->path($image->relativePath);
        unlink($path);
        Storage::disk('public')->put($image->relativePath, $this->validWebp);

        $this->expectException(CatalogImportPublicationException::class);
        $this->expectExceptionMessage('identity');
        try {
            $publisher->compensate([$image]);
        } finally {
            Storage::disk('public')->assertExists($image->relativePath);
            $this->assertSame($this->validWebp, Storage::disk('public')->get($image->relativePath));
        }
    }

    public function test_post_link_failure_self_compensates_created_public_image(): void
    {
        $this->seedCatalogRoots();
        $run = $this->seedReviewedRun();
        $publisher = new CatalogImportImagePublisher(
            afterPublicLink: static fn (): never => throw new \RuntimeException('Controlled post-link failure.'),
        );

        try {
            $publisher->publish($run, $run->items()->firstOrFail());
            $this->fail('A post-link failure must abort image publication.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Controlled post-link failure.', $exception->getMessage());
        }

        Storage::disk('public')->assertMissing('catalog-imports/full-run-001/images/11889.webp');
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_post_link_tamper_is_preserved_for_manual_verification(): void
    {
        $this->seedCatalogRoots();
        $run = $this->seedReviewedRun();
        $publisher = new CatalogImportImagePublisher(
            afterPublicLink: static function (string $path): void {
                Storage::disk('public')->put($path, 'changed-after-link');
            },
        );

        try {
            $publisher->publish($run, $run->items()->firstOrFail());
            $this->fail('Changed post-link evidence must not be accepted or deleted.');
        } catch (CatalogImportPublicationException $exception) {
            $this->assertStringContainsString('manual verification', $exception->getMessage());
        }

        $path = 'catalog-imports/full-run-001/images/11889.webp';
        Storage::disk('public')->assertExists($path);
        $this->assertSame('changed-after-link', Storage::disk('public')->get($path));
    }

    public function test_quarantine_plan_rejects_nested_path_with_valid_owned_basename(): void
    {
        $run = CatalogImportRun::create([
            'provider' => 'rimskie.com',
            'external_run_id' => 'full-run-001',
            'status' => CatalogImportRun::STATUS_PUBLISHED,
        ]);

        $this->expectException(CatalogImportPublicationException::class);
        $this->expectExceptionMessage('outside run ownership');

        (new CatalogImportImagePublisher)->expectedQuarantinePlan($run, [[
            'relative_path' => 'catalog-imports/full-run-001/images/nested/11889.webp',
            'sha256' => hash('sha256', $this->validWebp),
            'byte_length' => strlen($this->validWebp),
            'created' => true,
        ]]);
    }

    public function test_quarantine_rejects_junction_before_writing_private_trash(): void
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            $this->markTestSkipped('This regression covers Windows junction traversal.');
        }
        $run = CatalogImportRun::create([
            'provider' => 'rimskie.com',
            'external_run_id' => 'full-run-001',
            'status' => CatalogImportRun::STATUS_PUBLISHED,
        ]);
        $publicPath = 'catalog-imports/full-run-001/images/11889.webp';
        Storage::disk('public')->put($publicPath, $this->validWebp);
        $publicStat = lstat(Storage::disk('public')->path($publicPath));
        $targetDirectory = Storage::disk('local')->path('rollback-junction-target');
        $junctionDirectory = Storage::disk('local')->path('catalog-import-rollbacks');
        mkdir($targetDirectory, 0700, true);
        file_put_contents($targetDirectory.DIRECTORY_SEPARATOR.'sentinel.txt', 'must-remain');
        if (! $this->createJunction($junctionDirectory, $targetDirectory)) {
            $this->markTestSkipped('This host does not permit creating a test junction.');
        }
        $publisher = new CatalogImportImagePublisher;
        $media = [[
            'relative_path' => $publicPath,
            'sha256' => hash('sha256', $this->validWebp),
            'byte_length' => strlen($this->validWebp),
            'created' => true,
            'creation_identity' => ['dev' => (int) $publicStat['dev'], 'ino' => (int) $publicStat['ino']],
        ]];

        try {
            $publisher->quarantinePlanned($publisher->planQuarantine($run, $media));
            $this->fail('Rollback trash below a junction must be rejected.');
        } catch (CatalogImportPublicationException $exception) {
            $this->assertStringContainsString('junction', $exception->getMessage());
            $this->assertSame('must-remain', file_get_contents($targetDirectory.DIRECTORY_SEPARATOR.'sentinel.txt'));
            $this->assertFileDoesNotExist(
                $targetDirectory.DIRECTORY_SEPARATOR.'full-run-001'.DIRECTORY_SEPARATOR.'images'
                    .DIRECTORY_SEPARATOR.'11889.webp'
            );
            Storage::disk('public')->assertExists($publicPath);
        } finally {
            @rmdir($junctionDirectory);
        }
    }

    public function test_quarantine_never_overwrites_preexisting_private_trash(): void
    {
        $run = CatalogImportRun::create([
            'provider' => 'rimskie.com',
            'external_run_id' => 'full-run-001',
            'status' => CatalogImportRun::STATUS_PUBLISHED,
        ]);
        $publicPath = 'catalog-imports/full-run-001/images/11889.webp';
        $trashPath = 'catalog-import-rollbacks/full-run-001/images/11889.webp';
        Storage::disk('public')->put($publicPath, $this->validWebp);
        Storage::disk('local')->put($trashPath, 'unowned-private-trash');
        $publicStat = lstat(Storage::disk('public')->path($publicPath));
        $media = [[
            'relative_path' => $publicPath,
            'sha256' => hash('sha256', $this->validWebp),
            'byte_length' => strlen($this->validWebp),
            'created' => true,
            'creation_identity' => ['dev' => (int) $publicStat['dev'], 'ino' => (int) $publicStat['ino']],
        ]];

        try {
            (new CatalogImportImagePublisher)->planQuarantine($run, $media);
            $this->fail('A preexisting private trash destination must abort quarantine.');
        } catch (CatalogImportPublicationException $exception) {
            $this->assertStringContainsString('trash already exists', $exception->getMessage());
        }

        $this->assertSame('unowned-private-trash', Storage::disk('local')->get($trashPath));
        $this->assertSame($this->validWebp, Storage::disk('public')->get($publicPath));
    }

    public function test_quarantine_preserves_same_bytes_public_replacement_after_link(): void
    {
        $run = CatalogImportRun::create([
            'provider' => 'rimskie.com',
            'external_run_id' => 'full-run-001',
            'status' => CatalogImportRun::STATUS_PUBLISHED,
        ]);
        $publicPath = 'catalog-imports/full-run-001/images/11889.webp';
        Storage::disk('public')->put($publicPath, $this->validWebp);
        $publicStat = lstat(Storage::disk('public')->path($publicPath));
        $media = [[
            'relative_path' => $publicPath,
            'sha256' => hash('sha256', $this->validWebp),
            'byte_length' => strlen($this->validWebp),
            'created' => true,
            'creation_identity' => ['dev' => (int) $publicStat['dev'], 'ino' => (int) $publicStat['ino']],
        ]];
        $publisher = new CatalogImportImagePublisher(
            afterQuarantineLink: function (QuarantinedCatalogImportImage $image): void {
                $path = Storage::disk('public')->path($image->publicRelativePath);
                unlink($path);
                Storage::disk('public')->put($image->publicRelativePath, $this->validWebp);
            },
        );
        $plan = $publisher->planQuarantine($run, $media);

        try {
            $publisher->quarantinePlanned($plan);
            $this->fail('A same-bytes replacement must not be deleted as run-owned media.');
        } catch (CatalogImportPublicationException $exception) {
            $this->assertStringContainsString('identity', $exception->getMessage());
        }

        Storage::disk('public')->assertExists($publicPath);
        $this->assertSame($this->validWebp, Storage::disk('public')->get($publicPath));
        $this->assertNotSame([], Storage::disk('local')->allFiles('catalog-import-rollbacks'));
    }

    public function test_restore_never_overwrites_conflicting_public_destination(): void
    {
        $publicPath = 'catalog-imports/full-run-001/images/11889.webp';
        $trashPath = 'catalog-import-rollbacks/full-run-001/images/11889.webp';
        Storage::disk('public')->put($publicPath, 'manual-public-file');
        Storage::disk('local')->put($trashPath, $this->validWebp);
        $trashStat = lstat(Storage::disk('local')->path($trashPath));
        $image = new QuarantinedCatalogImportImage(
            publicRelativePath: $publicPath,
            trashRelativePath: $trashPath,
            sha256: hash('sha256', $this->validWebp),
            byteLength: strlen($this->validWebp),
            fileIdentity: ['dev' => (int) $trashStat['dev'], 'ino' => (int) $trashStat['ino']],
        );

        try {
            (new CatalogImportImagePublisher)->restoreQuarantined([$image]);
            $this->fail('A conflicting public destination must abort media restore.');
        } catch (CatalogImportPublicationException $exception) {
            $this->assertStringContainsString('public image', $exception->getMessage());
        }

        $this->assertSame('manual-public-file', Storage::disk('public')->get($publicPath));
        $this->assertSame($this->validWebp, Storage::disk('local')->get($trashPath));
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
}
