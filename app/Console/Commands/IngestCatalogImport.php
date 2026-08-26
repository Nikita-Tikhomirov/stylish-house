<?php

namespace App\Console\Commands;

use App\Models\CatalogImportItem;
use App\Models\CatalogImportSource;
use App\Services\CatalogImport\CatalogImportIngestor;
use App\Services\CatalogImport\CatalogImportPackageValidator;
use Illuminate\Console\Command;
use Throwable;

class IngestCatalogImport extends Command
{
    protected $signature = 'catalog-import:ingest {manifest} {--dry-run}';

    protected $description = 'Validate and stage a private catalog import package';

    public function handle(
        CatalogImportPackageValidator $validator,
        CatalogImportIngestor $ingestor,
    ): int {
        $manifestPath = (string) $this->argument('manifest');
        try {
            $package = $validator->validate($manifestPath);
            $counts = $package->counts;
            if ((bool) $this->option('dry-run')) {
                $this->info(sprintf(
                    'Validated run=%s sources=%d products=%d memberships=%d images=%d warnings=%d',
                    $package->runId,
                    $counts['sources'],
                    $counts['products'],
                    $counts['memberships'],
                    $counts['images'],
                    count($package->warnings),
                ));

                return self::SUCCESS;
            }

            $run = $ingestor->ingest($package);
            $needsReview = $run->sources()
                ->where('review_status', CatalogImportSource::REVIEW_NEEDS_REVIEW)
                ->count()
                + $run->items()
                    ->where('review_status', CatalogImportItem::STATUS_NEEDS_REVIEW)
                    ->count();
            $warningCount = $run->sources->sum(
                static fn (CatalogImportSource $source): int => count($source->warnings ?? []),
            ) + $run->items->sum(
                static fn (CatalogImportItem $item): int => count($item->warnings ?? []),
            );
            $this->info(sprintf(
                'Staged run=%s sources=%d products=%d memberships=%d images=%d needs_review=%d warnings=%d',
                $package->runId,
                $counts['sources'],
                $counts['products'],
                $counts['memberships'],
                $counts['images'],
                $needsReview,
                $warningCount,
            ));

            return self::SUCCESS;
        } catch (Throwable $error) {
            $this->error(sprintf('Manifest %s: %s', $manifestPath, $error->getMessage()));

            return self::FAILURE;
        }
    }
}
