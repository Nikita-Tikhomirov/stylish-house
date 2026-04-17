<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\ProductImageThumbnailService;
use Illuminate\Console\Command;

class GenerateProductThumbnails extends Command
{
    protected $signature = 'products:generate-thumbnails
        {--category-id=* : Process only these category IDs}
        {--subcategory-id=* : Process only these subcategory IDs}
        {--product-id=* : Process only these product IDs}
        {--width=600 : Thumbnail width}
        {--height=600 : Thumbnail height}
        {--quality=82 : WebP quality (1-100)}
        {--chunk=200 : Chunk size}
        {--limit=0 : Max products to process}
        {--only-missing : Skip products if thumbnail already exists}
        {--force : Regenerate existing thumbnails}
        {--dry-run : Do not write files, only show what would happen}
        {--report= : Save JSON report to storage/app/<path>.json}';

    protected $description = 'Generate thumbnails for existing product images';

    public function handle(ProductImageThumbnailService $thumbnailService): int
    {
        $width = max(1, (int)$this->option('width'));
        $height = max(1, (int)$this->option('height'));
        $quality = min(100, max(1, (int)$this->option('quality')));
        $chunk = max(1, (int)$this->option('chunk'));
        $limit = max(0, (int)$this->option('limit'));
        $onlyMissing = (bool)$this->option('only-missing');
        $force = (bool)$this->option('force');
        $dryRun = (bool)$this->option('dry-run');

        $categoryIds = $this->sanitizeIds((array)$this->option('category-id'));
        $subcategoryIds = $this->sanitizeIds((array)$this->option('subcategory-id'));
        $productIds = $this->sanitizeIds((array)$this->option('product-id'));

        $query = Product::query()
            ->whereNotNull('image_path')
            ->where('image_path', '<>', '');

        if (!empty($categoryIds)) {
            $query->whereIn('category_id', $categoryIds);
        }

        if (!empty($subcategoryIds)) {
            $query->whereIn('subcategory_id', $subcategoryIds);
        }

        if (!empty($productIds)) {
            $query->whereIn('id', $productIds);
        }

        $totalAvailable = (clone $query)->count();
        $targetTotal = $limit > 0 ? min($limit, $totalAvailable) : $totalAvailable;

        if ($targetTotal === 0) {
            $this->warn('No products matched filters.');
            return self::SUCCESS;
        }

        $this->info("Matched products: {$targetTotal}");
        $this->line('Settings: '
            . "width={$width}, height={$height}, quality={$quality}, chunk={$chunk}, "
            . "only_missing=" . ($onlyMissing ? 'yes' : 'no') . ', '
            . "force=" . ($force ? 'yes' : 'no') . ', '
            . "dry_run=" . ($dryRun ? 'yes' : 'no'));

        $processed = 0;
        $generated = 0;
        $skipped = 0;
        $missingSource = 0;
        $errors = 0;
        $errorItems = [];

        $bar = $this->output->createProgressBar($targetTotal);
        $bar->start();

        $query->orderBy('id')->chunkById($chunk, function ($products) use (
            $thumbnailService,
            $width,
            $height,
            $quality,
            $onlyMissing,
            $force,
            $dryRun,
            $targetTotal,
            &$processed,
            &$generated,
            &$skipped,
            &$missingSource,
            &$errors,
            &$errorItems,
            $bar
        ) {
            foreach ($products as $product) {
                if ($processed >= $targetTotal) {
                    return false;
                }

                if ($onlyMissing && !$force && $thumbnailService->hasThumbnailForPath($product->image_path)) {
                    $processed++;
                    $skipped++;
                    $bar->advance();
                    continue;
                }

                $result = $thumbnailService->generateForProduct($product, [
                    'width' => $width,
                    'height' => $height,
                    'quality' => $quality,
                    'force' => $force,
                    'dry_run' => $dryRun,
                ]);

                $status = $result['status'] ?? 'unknown';

                if ($status === 'generated' || $status === 'dry_run') {
                    $generated++;
                } elseif ($status === 'missing_source') {
                    $missingSource++;
                } elseif ($status === 'error') {
                    $errors++;
                    $errorItems[] = [
                        'product_id' => $product->id,
                        'image_path' => $product->image_path,
                        'reason' => $result['reason'] ?? 'unknown_error',
                    ];
                } else {
                    $skipped++;
                }

                $processed++;
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);

        $this->table(
            ['Metric', 'Value'],
            [
                ['Processed', $processed],
                ['Generated', $generated],
                ['Skipped', $skipped],
                ['Missing source', $missingSource],
                ['Errors', $errors],
            ]
        );

        $reportPath = $this->option('report');
        if (is_string($reportPath) && $reportPath !== '') {
            $this->writeReport($reportPath, [
                'processed' => $processed,
                'generated' => $generated,
                'skipped' => $skipped,
                'missing_source' => $missingSource,
                'errors' => $errors,
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
                    'only_missing' => $onlyMissing,
                    'force' => $force,
                    'dry_run' => $dryRun,
                ],
                'error_items' => $errorItems,
            ]);
        }

        return self::SUCCESS;
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

