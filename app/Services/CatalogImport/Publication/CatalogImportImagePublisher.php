<?php

namespace App\Services\CatalogImport\Publication;

use App\Data\CatalogImport\PublishedCatalogImportImage;
use App\Data\CatalogImport\QuarantinedCatalogImportImage;
use App\Models\CatalogImportItem;
use App\Models\CatalogImportRun;
use Closure;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class CatalogImportImagePublisher
{
    public function __construct(
        private readonly ?Closure $afterPublicLink = null,
        private readonly ?Closure $afterQuarantineLink = null,
    ) {}

    public function verifyPrivate(CatalogImportRun $run, CatalogImportItem $item): void
    {
        $path = $item->source_image_path;
        $sha256 = $item->source_image_sha256;
        $byteLength = $item->source_image_byte_length;
        if (! is_string($path) || ! is_string($sha256) || ! is_int($byteLength)
            || ! preg_match('/^[a-f0-9]{64}$/D', $sha256) || $byteLength < 1) {
            $this->fail('private image metadata is incomplete');
        }
        if ($path !== $this->destination($run, $item)) {
            $this->fail('private image ownership does not match its run and item identity');
        }

        $absolutePath = $this->containedRegularFile(Storage::disk('local'), $path, 'private image');
        $this->verifyFile($absolutePath, $sha256, $byteLength, true, 'private image');
    }

    public function destination(CatalogImportRun $run, CatalogImportItem $item): string
    {
        if (! preg_match('/^[a-z0-9][a-z0-9._-]{0,127}$/iD', $run->external_run_id)
            || ! preg_match('/^\d{1,32}$/D', $item->external_id)) {
            $this->fail('unsafe publication image identity');
        }

        return 'catalog-imports/'.$run->external_run_id.'/images/'.$item->external_id.'.webp';
    }

    public function assertPublicDestinationCompatible(CatalogImportRun $run, CatalogImportItem $item): void
    {
        $relativePath = $this->destination($run, $item);
        $disk = Storage::disk('public');
        $this->assertSafeCreationPath($disk, dirname($relativePath), 'public image directory');
        if (! $disk->exists($relativePath)) {
            return;
        }
        $path = $this->containedRegularFile($disk, $relativePath, 'public image');
        $this->verifyFile(
            $path,
            (string) $item->source_image_sha256,
            (int) $item->source_image_byte_length,
            true,
            'public image',
        );
    }

    public function publish(CatalogImportRun $run, CatalogImportItem $item): PublishedCatalogImportImage
    {
        $this->verifyPrivate($run, $item);
        $relativePath = $this->destination($run, $item);
        $this->assertPublicDestinationCompatible($run, $item);
        $disk = Storage::disk('public');
        if ($disk->exists($relativePath)) {
            return new PublishedCatalogImportImage(
                relativePath: $relativePath,
                databasePath: 'storage/'.$relativePath,
                sha256: $item->source_image_sha256,
                byteLength: $item->source_image_byte_length,
                created: false,
            );
        }

        $directory = dirname($relativePath);
        $this->assertSafeCreationPath($disk, $directory, 'public image directory');
        $disk->makeDirectory($directory);
        $this->assertContainedDirectory($disk, $directory, 'public image directory');
        $sourcePath = $this->containedRegularFile(
            Storage::disk('local'),
            $item->source_image_path,
            'private image',
        );
        $destinationPath = $disk->path($relativePath);
        $temporaryRelativePath = $relativePath.'.'.bin2hex(random_bytes(12)).'.tmp';
        $temporaryPath = $disk->path($temporaryRelativePath);
        $input = @fopen($sourcePath, 'rb');
        $output = @fopen($temporaryPath, 'xb');
        $temporaryIdentity = is_resource($output) ? $this->fileIdentity($temporaryPath) : null;
        if ($input === false || $output === false) {
            if (is_resource($input)) {
                fclose($input);
            }
            if (is_resource($output)) {
                fclose($output);
            }
            if ($temporaryIdentity !== null) {
                $this->removeOwnedFile(
                    $disk,
                    $temporaryRelativePath,
                    'public image temporary copy',
                    $temporaryIdentity,
                );
            }
            $this->fail('public image temporary copy cannot be created exclusively');
        }

        $temporaryExists = true;
        $copyComplete = false;
        $destinationCreated = false;
        try {
            try {
                $copied = stream_copy_to_stream($input, $output);
                if ($copied !== $item->source_image_byte_length || ! fflush($output)) {
                    $this->fail('public image copy has an unexpected byte length');
                }
                $copyComplete = true;
            } finally {
                fclose($input);
                fclose($output);
            }
            $this->verifyPrivate($run, $item);
            $this->verifyFile(
                $temporaryPath,
                $item->source_image_sha256,
                $item->source_image_byte_length,
                true,
                'public image temporary copy',
            );
            $this->verifyPrivate($run, $item);
            $this->assertSafeCreationPath($disk, $directory, 'public image directory');
            if (! @link($temporaryPath, $destinationPath)) {
                $this->removeOwnedFile(
                    $disk,
                    $temporaryRelativePath,
                    'public image temporary copy',
                    $temporaryIdentity,
                    $item->source_image_sha256,
                    $item->source_image_byte_length,
                );
                $temporaryExists = false;
                if (! file_exists($destinationPath)) {
                    $this->fail('public image cannot be atomically published without replacement');
                }
                $this->assertPublicDestinationCompatible($run, $item);

                return new PublishedCatalogImportImage(
                    relativePath: $relativePath,
                    databasePath: 'storage/'.$relativePath,
                    sha256: $item->source_image_sha256,
                    byteLength: $item->source_image_byte_length,
                    created: false,
                );
            }
            $destinationCreated = true;
            $linkedPath = $this->containedRegularFile($disk, $relativePath, 'public image', false);
            $this->assertFileIdentity($linkedPath, $temporaryIdentity, 'public image');
            $this->verifyFile(
                $linkedPath,
                $item->source_image_sha256,
                $item->source_image_byte_length,
                true,
                'public image',
            );
            $this->removeOwnedFile(
                $disk,
                $temporaryRelativePath,
                'public image temporary copy',
                $temporaryIdentity,
                $item->source_image_sha256,
                $item->source_image_byte_length,
                true,
            );
            $temporaryExists = false;
            if ($this->afterPublicLink !== null) {
                ($this->afterPublicLink)($relativePath);
            }
            $publishedPath = $this->containedRegularFile($disk, $relativePath, 'public image');
            $this->assertFileIdentity($publishedPath, $temporaryIdentity, 'public image');
            $this->verifyFile(
                $publishedPath,
                $item->source_image_sha256,
                $item->source_image_byte_length,
                true,
                'public image',
            );
            $this->verifyPrivate($run, $item);

            return new PublishedCatalogImportImage(
                relativePath: $relativePath,
                databasePath: 'storage/'.$relativePath,
                sha256: $item->source_image_sha256,
                byteLength: $item->source_image_byte_length,
                created: true,
                creationIdentity: $temporaryIdentity,
            );
        } catch (Throwable $error) {
            if (is_resource($input)) {
                fclose($input);
            }
            if (is_resource($output)) {
                fclose($output);
            }
            $cleanupError = null;
            if ($temporaryExists && file_exists($temporaryPath)) {
                try {
                    $this->removeOwnedFile(
                        $disk,
                        $temporaryRelativePath,
                        'public image temporary copy',
                        $temporaryIdentity,
                        $copyComplete ? $item->source_image_sha256 : null,
                        $copyComplete ? $item->source_image_byte_length : null,
                        $destinationCreated,
                    );
                    $temporaryExists = false;
                } catch (Throwable $temporaryCleanupError) {
                    $cleanupError = $temporaryCleanupError;
                }
            }
            if ($destinationCreated && ! $temporaryExists) {
                try {
                    $this->removeOwnedFile(
                        $disk,
                        $relativePath,
                        'failed public image',
                        $temporaryIdentity,
                        $item->source_image_sha256,
                        $item->source_image_byte_length,
                    );
                } catch (Throwable $destinationCleanupError) {
                    $cleanupError = $destinationCleanupError;
                }
            }
            if ($cleanupError !== null) {
                throw new CatalogImportManualVerificationException(
                    'Catalog import image cleanup could not prove ownership; evidence was preserved and manual verification is required.',
                    0,
                    $cleanupError,
                );
            }
            throw $error;
        }
    }

    /** @param array<int, PublishedCatalogImportImage> $images */
    public function compensate(array $images): void
    {
        $disk = Storage::disk('public');
        foreach (array_reverse($images) as $image) {
            if (! $image->created) {
                continue;
            }
            if (! is_array($image->creationIdentity)
                || ! isset($image->creationIdentity['dev'], $image->creationIdentity['ino'])) {
                $this->fail('failed publication image creation identity is unavailable; manual verification is required');
            }
            if (is_link($disk->path($image->relativePath))) {
                $this->fail('failed publication image became a symbolic link; manual verification is required');
            }
            if (! $disk->exists($image->relativePath)) {
                continue;
            }
            $path = $this->containedRegularFile($disk, $image->relativePath, 'public image');
            $this->assertFileIdentity($path, $image->creationIdentity, 'public image');
            $this->verifyFile($path, $image->sha256, $image->byteLength, true, 'public image');
            $path = $this->containedRegularFile($disk, $image->relativePath, 'public image');
            $this->assertFileIdentity($path, $image->creationIdentity, 'public image');
            if (! @unlink($path)) {
                $this->fail('failed publication image cannot be compensated; manual verification is required');
            }
        }
    }

    /** @param array<string, mixed> $snapshot */
    /** @return array{dev: int, ino: int} */
    public function assertPublishedSnapshot(array $snapshot): array
    {
        $relativePath = $snapshot['relative_path'] ?? null;
        $sha256 = $snapshot['sha256'] ?? null;
        $byteLength = $snapshot['byte_length'] ?? null;
        if (! is_string($relativePath) || ! is_string($sha256) || ! is_int($byteLength)) {
            $this->fail('published image snapshot is invalid');
        }
        $path = $this->containedRegularFile(Storage::disk('public'), $relativePath, 'public image');
        $this->verifyFile($path, $sha256, $byteLength, true, 'public image');

        return $this->fileIdentity($path);
    }

    public function assertPublishedImage(PublishedCatalogImportImage $image): void
    {
        $actualIdentity = $this->assertPublishedSnapshot($image->snapshot());
        if ($image->created) {
            $identity = $this->validatedIdentity($image->creationIdentity, 'public image');
            if ($actualIdentity !== $identity) {
                $this->fail('public image file identity changed');
            }
        }
    }

    /** @param array<string, mixed> $snapshot */
    public function assertOwnedPublishedSnapshot(array $snapshot): void
    {
        $created = $snapshot['created'] ?? null;
        if ($created === false) {
            if (($snapshot['creation_identity'] ?? null) !== null) {
                $this->fail('pre-existing published image snapshot claims a creation identity');
            }
            $this->assertPublishedSnapshot($snapshot);

            return;
        }
        if ($created !== true) {
            $this->fail('published image snapshot ownership is invalid');
        }
        $actualIdentity = $this->assertPublishedSnapshot($snapshot);
        $expectedIdentity = $this->validatedIdentity(
            $snapshot['creation_identity'] ?? null,
            'published image snapshot',
        );
        if ($actualIdentity !== $expectedIdentity) {
            $this->fail('published image snapshot file identity changed');
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $mediaSnapshots
     * @return array<int, QuarantinedCatalogImportImage>
     */
    public function planQuarantine(CatalogImportRun $run, array $mediaSnapshots): array
    {
        $plan = $this->expectedQuarantinePlan($run, $mediaSnapshots);
        foreach ($plan as $image) {
            $identity = $this->assertPublishedSnapshot([
                'relative_path' => $image->publicRelativePath,
                'sha256' => $image->sha256,
                'byte_length' => $image->byteLength,
            ]);
            if ($identity !== $this->validatedIdentity($image->fileIdentity, 'published image snapshot')) {
                $this->fail('published image snapshot file identity changed');
            }
            if (Storage::disk('local')->exists($image->trashRelativePath)) {
                $this->fail('unowned private rollback trash already exists');
            }
        }

        return $plan;
    }

    /**
     * Builds the deterministic journal payload without requiring media to still be
     * public. This is used to authenticate a journal recovered after a crash.
     *
     * @param  array<int, array<string, mixed>>  $mediaSnapshots
     * @return array<int, QuarantinedCatalogImportImage>
     */
    public function expectedQuarantinePlan(CatalogImportRun $run, array $mediaSnapshots): array
    {
        if (! preg_match('/^[a-z0-9][a-z0-9._-]{0,127}$/iD', $run->external_run_id)) {
            $this->fail('unsafe rollback run identity');
        }

        $plan = [];
        $seenDestinations = [];
        foreach ($mediaSnapshots as $snapshot) {
            if (($snapshot['created'] ?? null) !== true) {
                continue;
            }
            $publicRelativePath = $snapshot['relative_path'] ?? null;
            $sha256 = $snapshot['sha256'] ?? null;
            $byteLength = $snapshot['byte_length'] ?? null;
            $identity = $snapshot['creation_identity'] ?? null;
            if (! is_string($publicRelativePath) || ! is_string($sha256)
                || ! preg_match('/^[a-f0-9]{64}$/D', $sha256)
                || ! is_int($byteLength) || $byteLength < 1) {
                $this->fail('published image snapshot is invalid');
            }
            $expectedPrefix = 'catalog-imports/'.$run->external_run_id.'/images/';
            $filename = basename($publicRelativePath);
            if (! preg_match('/^\d{1,32}\.webp$/D', $filename)
                || $publicRelativePath !== $expectedPrefix.$filename) {
                $this->fail('published image snapshot is outside run ownership');
            }
            $identity = $this->validatedIdentity(
                is_array($identity) ? $identity : null,
                'published image snapshot',
            );
            $trashRelativePath = 'catalog-import-rollbacks/'.$run->external_run_id
                .'/images/'.basename($publicRelativePath);
            if (isset($seenDestinations[$trashRelativePath])) {
                $this->fail('rollback image journal contains a duplicate destination');
            }
            $seenDestinations[$trashRelativePath] = true;
            $plan[] = new QuarantinedCatalogImportImage(
                publicRelativePath: $publicRelativePath,
                trashRelativePath: $trashRelativePath,
                sha256: $sha256,
                byteLength: $byteLength,
                fileIdentity: $identity,
            );
        }

        return $plan;
    }

    /** @param array<int, QuarantinedCatalogImportImage> $images */
    public function quarantinePlanned(array $images): void
    {
        foreach ($images as $image) {
            $public = Storage::disk('public');
            $local = Storage::disk('local');
            $identity = $this->validatedIdentity($image->fileIdentity, 'rollback image');
            $publicExists = $public->exists($image->publicRelativePath);
            $trashExists = $local->exists($image->trashRelativePath);
            if (! $publicExists && ! $trashExists) {
                $this->fail('rollback image is missing from both public storage and durable trash');
            }
            if ($trashExists) {
                $trashPath = $this->containedRegularFile(
                    $local,
                    $image->trashRelativePath,
                    'private rollback trash image',
                    ! $publicExists,
                );
                $this->verifyFile(
                    $trashPath,
                    $image->sha256,
                    $image->byteLength,
                    true,
                    'private rollback trash image',
                );
                $this->assertFileIdentity($trashPath, $identity, 'private rollback trash image');
                if ($publicExists) {
                    $publicPath = $this->containedRegularFile(
                        $public,
                        $image->publicRelativePath,
                        'public image',
                        false,
                    );
                    $this->verifyFile($publicPath, $image->sha256, $image->byteLength, true, 'public image');
                    $this->assertFileIdentity($publicPath, $identity, 'public image');
                    $publicPath = $this->containedRegularFile(
                        $public,
                        $image->publicRelativePath,
                        'public image',
                        false,
                    );
                    $this->assertFileIdentity($publicPath, $identity, 'public image');
                    if (! @unlink($publicPath)) {
                        $this->fail('public image cannot be removed after durable quarantine link');
                    }
                    $trashPath = $this->containedRegularFile(
                        $local,
                        $image->trashRelativePath,
                        'private rollback trash image',
                    );
                    $this->verifyFile(
                        $trashPath,
                        $image->sha256,
                        $image->byteLength,
                        true,
                        'private rollback trash image',
                    );
                    $this->assertFileIdentity($trashPath, $identity, 'private rollback trash image');
                }

                continue;
            }

            $publicPath = $this->containedRegularFile($public, $image->publicRelativePath, 'public image');
            $this->verifyFile($publicPath, $image->sha256, $image->byteLength, true, 'public image');
            $this->assertFileIdentity($publicPath, $identity, 'public image');
            $trashDirectory = dirname($image->trashRelativePath);
            $this->assertSafeCreationPath($local, $trashDirectory, 'private rollback trash');
            $local->makeDirectory($trashDirectory);
            $this->assertContainedDirectory($local, $trashDirectory, 'private rollback trash');
            $trashPath = $local->path($image->trashRelativePath);
            if (! @link($publicPath, $trashPath)) {
                $this->fail('public image cannot be linked into durable rollback trash without replacement');
            }
            if ($this->afterQuarantineLink !== null) {
                ($this->afterQuarantineLink)($image);
            }
            $trashPath = $this->containedRegularFile(
                $local,
                $image->trashRelativePath,
                'private rollback trash image',
                false,
            );
            $this->verifyFile(
                $trashPath,
                $image->sha256,
                $image->byteLength,
                true,
                'private rollback trash image',
            );
            $this->assertFileIdentity($trashPath, $identity, 'private rollback trash image');
            $publicPath = $this->containedRegularFile(
                $public,
                $image->publicRelativePath,
                'public image',
                false,
            );
            $this->assertFileIdentity($publicPath, $identity, 'public image');
            if (! @unlink($publicPath)) {
                $this->fail('public image cannot be removed after durable quarantine link');
            }
            $trashPath = $this->containedRegularFile(
                $local,
                $image->trashRelativePath,
                'private rollback trash image',
            );
            $this->verifyFile(
                $trashPath,
                $image->sha256,
                $image->byteLength,
                true,
                'private rollback trash image',
            );
            $this->assertFileIdentity($trashPath, $identity, 'private rollback trash image');
        }
    }

    /** @param array<int, QuarantinedCatalogImportImage> $images */
    public function restoreQuarantined(array $images): void
    {
        foreach (array_reverse($images) as $image) {
            $local = Storage::disk('local');
            $public = Storage::disk('public');
            $identity = $this->validatedIdentity($image->fileIdentity, 'rollback image');
            $publicExists = $public->exists($image->publicRelativePath);
            $trashExists = $local->exists($image->trashRelativePath);
            if (! $publicExists && ! $trashExists) {
                $this->fail('rollback image is missing from both public storage and durable trash');
            }
            if ($publicExists) {
                $publicPath = $this->containedRegularFile(
                    $public,
                    $image->publicRelativePath,
                    'public image',
                    ! $trashExists,
                );
                $this->verifyFile($publicPath, $image->sha256, $image->byteLength, true, 'public image');
                $this->assertFileIdentity($publicPath, $identity, 'public image');
                if ($trashExists) {
                    $trashPath = $this->containedRegularFile(
                        $local,
                        $image->trashRelativePath,
                        'private rollback trash image',
                        false,
                    );
                    $this->verifyFile(
                        $trashPath,
                        $image->sha256,
                        $image->byteLength,
                        true,
                        'private rollback trash image',
                    );
                    $this->assertFileIdentity($trashPath, $identity, 'private rollback trash image');
                    $trashPath = $this->containedRegularFile(
                        $local,
                        $image->trashRelativePath,
                        'private rollback trash image',
                        false,
                    );
                    $this->assertFileIdentity($trashPath, $identity, 'private rollback trash image');
                    if (! @unlink($trashPath)) {
                        $this->fail('private rollback trash cannot be cleared after media restore');
                    }
                    $publicPath = $this->containedRegularFile(
                        $public,
                        $image->publicRelativePath,
                        'public image',
                    );
                    $this->verifyFile(
                        $publicPath,
                        $image->sha256,
                        $image->byteLength,
                        true,
                        'public image',
                    );
                    $this->assertFileIdentity($publicPath, $identity, 'public image');
                }

                continue;
            }

            $trashPath = $this->containedRegularFile(
                $local,
                $image->trashRelativePath,
                'private rollback trash image',
            );
            $this->assertFileIdentity($trashPath, $identity, 'private rollback trash image');
            $this->verifyFile(
                $trashPath,
                $image->sha256,
                $image->byteLength,
                true,
                'private rollback trash image',
            );
            $directory = dirname($image->publicRelativePath);
            $this->assertSafeCreationPath($public, $directory, 'public image directory');
            $public->makeDirectory($directory);
            $this->assertContainedDirectory($public, $directory, 'public image directory');
            $publicPath = $public->path($image->publicRelativePath);
            if (! @link($trashPath, $publicPath)) {
                $this->fail('rollback cannot restore quarantined public image without replacement');
            }
            $restored = $this->containedRegularFile(
                $public,
                $image->publicRelativePath,
                'public image',
                false,
            );
            $this->verifyFile($restored, $image->sha256, $image->byteLength, true, 'public image');
            $this->assertFileIdentity($restored, $identity, 'public image');
            $trashPath = $this->containedRegularFile(
                $local,
                $image->trashRelativePath,
                'private rollback trash image',
                false,
            );
            $this->assertFileIdentity($trashPath, $identity, 'private rollback trash image');
            if (! @unlink($trashPath)) {
                $this->fail('private rollback trash cannot be cleared after media restore');
            }
            $restored = $this->containedRegularFile($public, $image->publicRelativePath, 'public image');
            $this->verifyFile($restored, $image->sha256, $image->byteLength, true, 'public image');
            $this->assertFileIdentity($restored, $identity, 'public image');
        }
    }

    /** @param array<int, QuarantinedCatalogImportImage> $images */
    public function purgeQuarantined(array $images): void
    {
        foreach ($images as $image) {
            $local = Storage::disk('local');
            $public = Storage::disk('public');
            $identity = $this->validatedIdentity($image->fileIdentity, 'rollback image');
            $this->assertSafeCreationPath(
                $public,
                dirname($image->publicRelativePath),
                'public image directory',
            );
            if ($public->exists($image->publicRelativePath)) {
                $this->fail('rolled back public image unexpectedly exists; refusing private trash purge');
            }
            $this->assertSafeCreationPath(
                $local,
                dirname($image->trashRelativePath),
                'private rollback trash',
            );
            if (is_link($public->path($image->publicRelativePath))
                || is_link($local->path($image->trashRelativePath))) {
                $this->fail('rollback media cleanup encountered a symbolic link; manual verification is required');
            }
            if (! $local->exists($image->trashRelativePath)) {
                continue;
            }
            $trashPath = $this->containedRegularFile(
                $local,
                $image->trashRelativePath,
                'private rollback trash image',
            );
            $this->verifyFile(
                $trashPath,
                $image->sha256,
                $image->byteLength,
                true,
                'private rollback trash image',
            );
            $this->assertFileIdentity($trashPath, $identity, 'private rollback trash image');
            $trashPath = $this->containedRegularFile(
                $local,
                $image->trashRelativePath,
                'private rollback trash image',
            );
            $this->assertFileIdentity($trashPath, $identity, 'private rollback trash image');
            if (! @unlink($trashPath)) {
                $this->fail('private rollback trash image cannot be purged');
            }
        }
    }

    private function assertContainedDirectory(
        FilesystemAdapter $disk,
        string $relativePath,
        string $label,
    ): void {
        $root = realpath($disk->path(''));
        $directory = realpath($disk->path($relativePath));
        if ($root === false || $directory === false || ! is_dir($directory)) {
            $this->fail($label.' is unavailable');
        }
        $prefix = rtrim($root, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
        $comparisonPrefix = DIRECTORY_SEPARATOR === '\\' ? strtolower($prefix) : $prefix;
        $comparisonDirectory = DIRECTORY_SEPARATOR === '\\' ? strtolower($directory) : $directory;
        if (! str_starts_with($comparisonDirectory.DIRECTORY_SEPARATOR, $comparisonPrefix)) {
            $this->fail($label.' escapes its storage root');
        }
        $this->assertCanonicalAncestors($disk, $relativePath, $label);
    }

    private function assertSafeCreationPath(
        FilesystemAdapter $disk,
        string $relativePath,
        string $label,
    ): void {
        $this->assertCanonicalAncestors($disk, $relativePath, $label);
        $root = realpath($disk->path(''));
        if ($root === false) {
            $this->fail($label.' storage root is unavailable');
        }
        $candidate = $this->normalizeAbsolutePath($disk->path($relativePath));
        $prefix = $this->normalizeAbsolutePath($root).DIRECTORY_SEPARATOR;
        if (! str_starts_with($candidate.DIRECTORY_SEPARATOR, $prefix)) {
            $this->fail($label.' escapes its storage root');
        }
    }

    private function containedRegularFile(
        FilesystemAdapter $disk,
        string $relativePath,
        string $label,
        bool $requireExclusive = true,
    ): string {
        clearstatcache(true, $disk->path($relativePath));
        if ($relativePath === '' || str_contains($relativePath, "\0")
            || str_contains($relativePath, '\\') || str_starts_with($relativePath, '/')
            || preg_match('/(^|\/)\.\.?($|\/)/D', $relativePath)
            || preg_match('/^[a-z]:/iD', $relativePath)) {
            $this->fail($label.' path is unsafe');
        }

        $rootPath = $disk->path('');
        $root = realpath($rootPath);
        $candidate = $disk->path($relativePath);
        $resolved = realpath($candidate);
        if ($root === false || $resolved === false || ! is_file($resolved)) {
            $this->fail($label.' file is missing');
        }
        $prefix = rtrim($root, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
        $comparisonPrefix = DIRECTORY_SEPARATOR === '\\' ? strtolower($prefix) : $prefix;
        $comparisonPath = DIRECTORY_SEPARATOR === '\\' ? strtolower($resolved) : $resolved;
        if (! str_starts_with($comparisonPath, $comparisonPrefix)) {
            $this->fail($label.' escapes its storage root');
        }

        $this->assertCanonicalAncestors($disk, $relativePath, $label);
        $stat = @lstat($resolved);
        if ($stat === false || (($stat['mode'] ?? 0) & 0170000) !== 0100000
            || ($requireExclusive && ($stat['nlink'] ?? 1) !== 1)) {
            $this->fail($label.' is not an exclusive regular file');
        }

        return $resolved;
    }

    private function assertCanonicalAncestors(
        FilesystemAdapter $disk,
        string $relativePath,
        string $label,
    ): void {
        $rootLexical = $this->normalizeAbsolutePath($disk->path(''));
        $rootResolved = realpath($disk->path(''));
        if ($rootResolved === false
            || $this->normalizeAbsolutePath($rootResolved) !== $rootLexical
            || is_link($disk->path(''))) {
            $this->fail($label.' storage root traverses a symbolic link or junction');
        }

        $current = rtrim($disk->path(''), DIRECTORY_SEPARATOR);
        foreach (explode('/', $relativePath) as $part) {
            $current .= DIRECTORY_SEPARATOR.$part;
            if (! file_exists($current) && ! is_link($current)) {
                continue;
            }
            $resolved = realpath($current);
            if ($resolved === false || is_link($current)
                || $this->normalizeAbsolutePath($resolved) !== $this->normalizeAbsolutePath($current)) {
                $this->fail($label.' traverses a symbolic link or junction');
            }
        }
    }

    private function normalizeAbsolutePath(string $path): string
    {
        $normalized = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);

        return DIRECTORY_SEPARATOR === '\\' ? mb_strtolower($normalized) : $normalized;
    }

    /** @return array{dev: int, ino: int} */
    private function fileIdentity(string $path): array
    {
        $stat = @lstat($path);
        if ($stat === false || ! isset($stat['dev'], $stat['ino'])) {
            $this->fail('created image file identity cannot be recorded');
        }

        return ['dev' => (int) $stat['dev'], 'ino' => (int) $stat['ino']];
    }

    /** @return array{dev: int, ino: int} */
    private function validatedIdentity(?array $identity, string $label): array
    {
        $keys = is_array($identity) ? array_keys($identity) : [];
        sort($keys, SORT_STRING);
        if (! is_array($identity) || $keys !== ['dev', 'ino']
            || ! is_int($identity['dev']) || ! is_int($identity['ino'])) {
            $this->fail($label.' file identity is unavailable; manual verification is required');
        }

        return ['dev' => $identity['dev'], 'ino' => $identity['ino']];
    }

    /** @param array{dev: int, ino: int} $expected */
    private function assertFileIdentity(string $path, array $expected, string $label): void
    {
        $actual = $this->fileIdentity($path);
        if ($actual !== $expected) {
            $this->fail($label.' file identity changed');
        }
    }

    /**
     * @param  array{dev: int, ino: int}  $identity
     */
    private function removeOwnedFile(
        FilesystemAdapter $disk,
        string $relativePath,
        string $label,
        array $identity,
        ?string $sha256 = null,
        ?int $byteLength = null,
        bool $allowHardLinks = false,
    ): void {
        $path = $this->containedRegularFile($disk, $relativePath, $label, ! $allowHardLinks);
        $this->assertFileIdentity($path, $identity, $label);
        if ($sha256 !== null && $byteLength !== null) {
            $this->verifyFile($path, $sha256, $byteLength, true, $label);
        }
        $path = $this->containedRegularFile($disk, $relativePath, $label, ! $allowHardLinks);
        $this->assertFileIdentity($path, $identity, $label);
        if (! @unlink($path)) {
            $this->fail($label.' cannot be removed; manual verification is required');
        }
    }

    private function verifyFile(
        string $path,
        string $sha256,
        int $byteLength,
        bool $requireWebp,
        string $label,
    ): void {
        clearstatcache(true, $path);
        $actualLength = filesize($path);
        $actualHash = hash_file('sha256', $path);
        if ($actualLength !== $byteLength || ! is_string($actualHash)
            || ! hash_equals($sha256, $actualHash)) {
            $this->fail($label.' hash or byte length changed');
        }
        if ($requireWebp) {
            $bytes = file_get_contents($path);
            if (! is_string($bytes) || ! $this->isWebp($bytes)) {
                $this->fail($label.' is not a structurally valid WebP');
            }
        }
    }

    private function isWebp(string $bytes): bool
    {
        if (strlen($bytes) < 20 || substr($bytes, 0, 4) !== 'RIFF'
            || substr($bytes, 8, 4) !== 'WEBP'
            || ! in_array(substr($bytes, 12, 4), ['VP8 ', 'VP8L', 'VP8X'], true)) {
            return false;
        }
        $riffSize = unpack('Vsize', substr($bytes, 4, 4));
        $chunkSize = unpack('Vsize', substr($bytes, 16, 4));
        if (! is_array($riffSize) || ! is_array($chunkSize)) {
            return false;
        }

        return strlen($bytes) === $riffSize['size'] + 8
            && 20 + $chunkSize['size'] + ($chunkSize['size'] % 2) <= strlen($bytes);
    }

    private function fail(string $message): never
    {
        throw new CatalogImportPublicationException('Catalog import publication preflight failed: '.$message.'.');
    }
}
