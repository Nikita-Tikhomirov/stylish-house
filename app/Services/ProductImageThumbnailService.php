<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic as Image;
use Throwable;

class ProductImageThumbnailService
{
    public function generateForProduct(Product $product, array $options = []): array
    {
        return $this->generateFromPath($product->image_path, $options + ['product_id' => $product->id]);
    }

    public function generateFromPath(?string $imagePath, array $options = []): array
    {
        if (!$imagePath) {
            return [
                'status' => 'skipped',
                'reason' => 'empty_image_path',
                'thumbnail_relative_path' => null,
                'thumbnail_public_path' => null,
            ];
        }

        $width = max(1, (int)($options['width'] ?? 600));
        $height = max(1, (int)($options['height'] ?? 600));
        $quality = min(100, max(1, (int)($options['quality'] ?? 82)));
        $force = (bool)($options['force'] ?? false);
        $dryRun = (bool)($options['dry_run'] ?? false);

        $thumbnailRelativePath = $this->buildThumbnailRelativePath($imagePath);
        $thumbnailPublicPath = 'storage/' . $thumbnailRelativePath;
        $disk = Storage::disk('public');

        if ($disk->exists($thumbnailRelativePath) && !$force) {
            return [
                'status' => 'skipped',
                'reason' => 'thumbnail_exists',
                'thumbnail_relative_path' => $thumbnailRelativePath,
                'thumbnail_public_path' => $thumbnailPublicPath,
            ];
        }

        $sourcePath = $this->resolveSourceAbsolutePath($imagePath);
        if (!$sourcePath || !is_file($sourcePath)) {
            return [
                'status' => 'missing_source',
                'reason' => 'source_not_found',
                'source_path' => $sourcePath,
                'thumbnail_relative_path' => $thumbnailRelativePath,
                'thumbnail_public_path' => $thumbnailPublicPath,
            ];
        }

        if ($dryRun) {
            return [
                'status' => 'dry_run',
                'reason' => 'dry_run',
                'source_path' => $sourcePath,
                'thumbnail_relative_path' => $thumbnailRelativePath,
                'thumbnail_public_path' => $thumbnailPublicPath,
            ];
        }

        try {
            $image = Image::make($sourcePath)
                ->orientate()
                ->fit($width, $height, function ($constraint) {
                    $constraint->upsize();
                })
                ->encode('webp', $quality);

            $dir = dirname($thumbnailRelativePath);
            if ($dir !== '.' && !$disk->exists($dir)) {
                $disk->makeDirectory($dir);
            }

            $disk->put($thumbnailRelativePath, (string)$image);

            return [
                'status' => 'generated',
                'reason' => 'ok',
                'source_path' => $sourcePath,
                'thumbnail_relative_path' => $thumbnailRelativePath,
                'thumbnail_public_path' => $thumbnailPublicPath,
                'width' => $width,
                'height' => $height,
                'quality' => $quality,
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'error',
                'reason' => $e->getMessage(),
                'source_path' => $sourcePath,
                'thumbnail_relative_path' => $thumbnailRelativePath,
                'thumbnail_public_path' => $thumbnailPublicPath,
            ];
        }
    }

    public function hasThumbnailForPath(?string $imagePath): bool
    {
        if (!$imagePath) {
            return false;
        }

        return Storage::disk('public')->exists($this->buildThumbnailRelativePath($imagePath));
    }

    public function buildThumbnailRelativePath(string $imagePath): string
    {
        $normalized = $this->normalizePath($imagePath);
        $hash = sha1($normalized);

        return 'thumbnails/products/' . substr($hash, 0, 2) . '/' . $hash . '.webp';
    }

    private function resolveSourceAbsolutePath(string $imagePath): ?string
    {
        $normalized = $this->normalizePath($imagePath);
        if ($normalized === '') {
            return null;
        }

        $candidates = [];
        $candidates[] = public_path($normalized);

        if (str_starts_with($normalized, 'storage/')) {
            $storageRelative = ltrim(substr($normalized, strlen('storage/')), '/');
            $candidates[] = storage_path('app/public/' . $storageRelative);
        } else {
            $candidates[] = storage_path('app/public/' . $normalized);
        }

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }

            $decoded = rawurldecode($candidate);
            if ($decoded !== $candidate && is_file($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    private function normalizePath(string $imagePath): string
    {
        $path = trim($imagePath);

        if ($path === '') {
            return '';
        }

        if (preg_match('#^https?://#i', $path)) {
            $parsedPath = parse_url($path, PHP_URL_PATH);
            $path = is_string($parsedPath) ? $parsedPath : '';
        }

        $path = str_replace('\\', '/', $path);
        $path = preg_replace('#/+#', '/', $path);

        return ltrim((string)$path, '/');
    }
}

