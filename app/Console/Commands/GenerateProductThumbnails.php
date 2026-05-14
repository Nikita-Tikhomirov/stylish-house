<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\ProductImageThumbnailService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class GenerateProductThumbnails extends Command
{
    protected $signature = 'products:generate-thumbnails
        {--category-id=* : Process only these category IDs}
        {--subcategory-id=* : Process only these subcategory IDs}
        {--product-id=* : Process only these product IDs}
        {--start-id=0 : Process products with ID greater than this value}
        {--width=400 : Thumbnail width}
        {--height=400 : Thumbnail height}
        {--quality=82 : WebP quality (1-100)}
        {--chunk=50 : Chunk size}
        {--limit=0 : Max products to process}
        {--sleep=50 : Milliseconds to sleep after each product}
        {--max-seconds=0 : Stop gracefully after this many seconds}
        {--only-missing : Skip products if thumbnail columns are already filled}
        {--force : Regenerate existing thumbnail files}
        {--skip-main : Do not process main product photos}
        {--skip-fabric : Do not process fabric/material photos}
        {--no-lock : Allow concurrent runs}
        {--dry-run : Do not write files, only show what would happen}
        {--report= : Save JSON report to storage/app/<path>.json}';

    protected $description = 'Generate thumbnails for product main and material images';

    public function handle(ProductImageThumbnailService $thumbnailService): int
    {
        $width = max(1, (int)$this->option('width'));
        $height = max(1, (int)$this->option('height'));
        $quality = min(100, max(1, (int)$this->option('quality')));
        $chunk = max(1, (int)$this->option('chunk'));
        $limit = max(0, (int)$this->option('limit'));
        $sleepMs = max(0, (int)$this->option('sleep'));
        $maxSeconds = max(0, (int)$this->option('max-seconds'));
        $startId = max(0, (int)$this->option('start-id'));
        $onlyMissing = (bool)$this->option('only-missing');
        $force = (bool)$this->option('force');
        $dryRun = (bool)$this->option('dry-run');
        $includeMain = !(bool)$this->option('skip-main');
        $includeFabric = !(bool)$this->option('skip-fabric');
        $canStoreThumbColumns = $this->canStoreThumbColumns();

        if (!$includeMain && !$includeFabric) {
            $this->error('Nothing selected: both --skip-main and --skip-fabric are enabled.');
            return self::FAILURE;
        }

        $lockHandle = null;
        if (!(bool)$this->option('no-lock')) {
            $lockHandle = $this->acquireLock();
            if (!$lockHandle) {
                $this->error('Another thumbnail generation process is already running.');
                return self::FAILURE;
            }
        }

        try {
            return $this->runGeneration(
                $thumbnailService,
                $width,
                $height,
                $quality,
                $chunk,
                $limit,
                $sleepMs,
                $maxSeconds,
                $startId,
                $onlyMissing,
                $force,
                $dryRun,
                $includeMain,
                $includeFabric,
                $canStoreThumbColumns
            );
        } finally {
            if ($lockHandle) {
                flock($lockHandle, LOCK_UN);
                fclose($lockHandle);
            }
        }
    }

    private function runGeneration(
        ProductImageThumbnailService $thumbnailService,
        int $width,
        int $height,
        int $quality,
        int $chunk,
        int $limit,
        int $sleepMs,
        int $maxSeconds,
        int $startId,
        bool $onlyMissing,
        bool $force,
        bool $dryRun,
        bool $includeMain,
        bool $includeFabric,
        bool $canStoreThumbColumns
    ): int {
        $startedAt = microtime(true);
        $categoryIds = $this->sanitizeIds((array)$this->option('category-id'));
        $subcategoryIds = $this->sanitizeIds((array)$this->option('subcategory-id'));
        $productIds = $this->sanitizeIds((array)$this->option('product-id'));

        $query = Product::query()->where('id', '>', $startId);

        if (!empty($categoryIds)) {
            $query->whereIn('category_id', $categoryIds);
        }

        if (!empty($subcategoryIds)) {
            $query->whereIn('subcategory_id', $subcategoryIds);
        }

        if (!empty($productIds)) {
            $query->whereIn('id', $productIds);
        }

        $query->where(function ($q) use ($includeMain, $includeFabric, $onlyMissing, $force, $canStoreThumbColumns) {
            if ($includeMain) {
                $q->where(function ($q2) use ($onlyMissing, $force, $canStoreThumbColumns) {
                    $q2->whereNotNull('image_path')->where('image_path', '<>', '');

                    if ($onlyMissing && !$force && $canStoreThumbColumns) {
                        $q2->where(function ($q3) {
                            $q3->whereNull('image_thumb_path')->orWhere('image_thumb_path', '');
                        });
                    }
                });
            }

            if ($includeFabric) {
                $method = $includeMain ? 'orWhere' : 'where';
                $q->{$method}(function ($q2) use ($onlyMissing, $force, $canStoreThumbColumns) {
                    $q2->whereNotNull('fabric_photo')->where('fabric_photo', '<>', '');

                    if ($onlyMissing && !$force && $canStoreThumbColumns) {
                        $q2->where(function ($q3) {
                            $q3->whereNull('fabric_thumb_path')->orWhere('fabric_thumb_path', '');
                        });
                    }
                });
            }
        });

        $totalAvailable = (clone $query)->count();
        $targetTotal = $limit > 0 ? min($limit, $totalAvailable) : $totalAvailable;

        if ($targetTotal === 0) {
            $this->warn('No products matched filters.');
            return self::SUCCESS;
        }

        $this->info("Matched products: {$targetTotal}");
        $this->line('Settings: '
            . "width={$width}, height={$height}, quality={$quality}, chunk={$chunk}, sleep_ms={$sleepMs}, "
            . "start_id={$startId}, max_seconds={$maxSeconds}, "
            . "main=" . ($includeMain ? 'yes' : 'no') . ', '
            . "fabric=" . ($includeFabric ? 'yes' : 'no') . ', '
            . "only_missing=" . ($onlyMissing ? 'yes' : 'no') . ', '
            . "force=" . ($force ? 'yes' : 'no') . ', '
            . "dry_run=" . ($dryRun ? 'yes' : 'no'));

        $stats = [
            'processed_products' => 0,
            'last_product_id' => $startId,
            'generated_main' => 0,
            'generated_fabric' => 0,
            'skipped_main' => 0,
            'skipped_fabric' => 0,
            'missing_source_main' => 0,
            'missing_source_fabric' => 0,
            'errors_main' => 0,
            'errors_fabric' => 0,
            'updated_db' => 0,
            'stopped_by_time_limit' => false,
        ];
        $errorItems = [];

        $bar = $this->output->createProgressBar($targetTotal);
        $bar->start();

        $query->orderBy('id')->chunkById($chunk, function ($products) use (
            $thumbnailService,
            $width,
            $height,
            $quality,
            $force,
            $dryRun,
            $sleepMs,
            $maxSeconds,
            $startedAt,
            $targetTotal,
            $includeMain,
            $includeFabric,
            $canStoreThumbColumns,
            &$stats,
            &$errorItems,
            $bar
        ) {
            foreach ($products as $product) {
                if ($stats['processed_products'] >= $targetTotal) {
                    return false;
                }

                if ($maxSeconds > 0 && (microtime(true) - $startedAt) >= $maxSeconds) {
                    $stats['stopped_by_time_limit'] = true;
                    return false;
                }

                $changed = false;
                $stats['last_product_id'] = (int)$product->id;

                if ($includeMain && !empty($product->image_path)) {
                    $changed = $this->processImage(
                        $thumbnailService,
                        $product,
                        'main',
                        $product->image_path,
                        'image_thumb_path',
                        $width,
                        $height,
                        $quality,
                        $force,
                        $dryRun,
                        $canStoreThumbColumns,
                        $stats,
                        $errorItems
                    ) || $changed;
                }

                if ($includeFabric && !empty($product->fabric_photo)) {
                    $changed = $this->processImage(
                        $thumbnailService,
                        $product,
                        'fabric',
                        $product->fabric_photo,
                        'fabric_thumb_path',
                        $width,
                        $height,
                        $quality,
                        $force,
                        $dryRun,
                        $canStoreThumbColumns,
                        $stats,
                        $errorItems
                    ) || $changed;
                }

                if ($changed && !$dryRun) {
                    $product->save();
                    $stats['updated_db']++;
                }

                $stats['processed_products']++;
                $bar->advance();

                if ($sleepMs > 0) {
                    usleep($sleepMs * 1000);
                }
            }
        });

        $bar->finish();
        $this->newLine(2);

        $this->table(
            ['Metric', 'Value'],
            [
                ['Processed products', $stats['processed_products']],
                ['Last product ID', $stats['last_product_id']],
                ['Generated main', $stats['generated_main']],
                ['Generated fabric', $stats['generated_fabric']],
                ['Skipped main', $stats['skipped_main']],
                ['Skipped fabric', $stats['skipped_fabric']],
                ['Missing source main', $stats['missing_source_main']],
                ['Missing source fabric', $stats['missing_source_fabric']],
                ['Errors main', $stats['errors_main']],
                ['Errors fabric', $stats['errors_fabric']],
                ['Updated DB rows', $stats['updated_db']],
                ['Stopped by time limit', $stats['stopped_by_time_limit'] ? 'yes' : 'no'],
            ]
        );

        $reportPath = $this->option('report');
        if (is_string($reportPath) && $reportPath !== '') {
            $this->writeReport($reportPath, [
                'stats' => $stats,
                'filters' => [
                    'category_id' => $categoryIds,
                    'subcategory_id' => $subcategoryIds,
                    'product_id' => $productIds,
                ],
                'options' => [
                    'width' => $width,
                    'height' => $height,
                    'quality' => $quality,
                    'chunk' => $chunk,
                    'limit' => $limit,
                    'sleep_ms' => $sleepMs,
                    'max_seconds' => $maxSeconds,
                    'start_id' => $startId,
                    'only_missing' => $onlyMissing,
                    'force' => $force,
                    'dry_run' => $dryRun,
                    'include_main' => $includeMain,
                    'include_fabric' => $includeFabric,
                ],
                'error_items' => $errorItems,
            ]);
        }

        if ($stats['stopped_by_time_limit']) {
            $this->warn('Stopped by time limit. Resume with --start-id=' . $stats['last_product_id']);
        }

        return self::SUCCESS;
    }

    private function processImage(
        ProductImageThumbnailService $thumbnailService,
        Product $product,
        string $type,
        string $sourcePath,
        string $thumbColumn,
        int $width,
        int $height,
        int $quality,
        bool $force,
        bool $dryRun,
        bool $canStoreThumbColumns,
        array &$stats,
        array &$errorItems
    ): bool {
        $result = $thumbnailService->generateFromPath($sourcePath, [
            'width' => $width,
            'height' => $height,
            'quality' => $quality,
            'force' => $force,
            'dry_run' => $dryRun,
        ]);

        $status = $result['status'] ?? 'unknown';
        $changed = false;

        if ($status === 'generated' || $status === 'dry_run') {
            $stats["generated_{$type}"]++;
        } elseif ($status === 'missing_source') {
            $stats["missing_source_{$type}"]++;
            $errorItems[] = [
                'product_id' => $product->id,
                'type' => $type,
                'source_path' => $sourcePath,
                'reason' => $result['reason'] ?? 'source_not_found',
            ];
        } elseif ($status === 'error') {
            $stats["errors_{$type}"]++;
            $errorItems[] = [
                'product_id' => $product->id,
                'type' => $type,
                'source_path' => $sourcePath,
                'reason' => $result['reason'] ?? 'unknown_error',
            ];
        } else {
            $stats["skipped_{$type}"]++;
        }

        if ($canStoreThumbColumns && !$dryRun && in_array($status, ['generated', 'skipped'], true) && !empty($result['thumbnail_public_path']) && $product->{$thumbColumn} !== $result['thumbnail_public_path']) {
            $product->{$thumbColumn} = $result['thumbnail_public_path'];
            $changed = true;
        }

        return $changed;
    }

    private function canStoreThumbColumns(): bool
    {
        return Schema::hasColumn('products', 'image_thumb_path')
            && Schema::hasColumn('products', 'fabric_thumb_path');
    }

    private function acquireLock()
    {
        $lockPath = storage_path('framework/product-thumbnails.lock');
        $dir = dirname($lockPath);

        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $handle = fopen($lockPath, 'c');
        if (!$handle) {
            return null;
        }

        if (!flock($handle, LOCK_EX | LOCK_NB)) {
            fclose($handle);
            return null;
        }

        ftruncate($handle, 0);
        fwrite($handle, (string)getmypid());

        return $handle;
    }

    private function sanitizeIds(array $raw): array
    {
        return array_values(array_unique(array_filter(array_map(
            static fn($value) => is_numeric($value) ? (int)$value : null,
            $raw
        ))));
    }

    private function writeReport(string $reportPathOption, array $payload): void
    {
        $reportPath = $this->resolveReportPath($reportPathOption);
        $dir = dirname($reportPath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        file_put_contents(
            $reportPath,
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        $this->info("Report saved: {$reportPath}");
    }

    private function resolveReportPath(string $path): string
    {
        if (str_starts_with($path, '/')) {
            return $path;
        }

        if (preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) === 1) {
            return $path;
        }

        return storage_path('app/' . ltrim($path, '/'));
    }
}
