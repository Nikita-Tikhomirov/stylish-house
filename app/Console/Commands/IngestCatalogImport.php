<?php

namespace App\Console\Commands;

use App\Exceptions\SafeCatalogImportException;
use App\Models\CatalogImportItem;
use App\Models\CatalogImportSource;
use App\Services\CatalogImport\CatalogImportIngestor;
use App\Services\CatalogImport\CatalogImportPackageValidator;
use App\Services\CatalogImport\CatalogImportRewritePlanner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

class IngestCatalogImport extends Command
{
    protected $signature = 'catalog-import:ingest {manifest} {--dry-run}';

    protected $description = 'Validate and stage a private catalog import package';

    public function handle(
        CatalogImportPackageValidator $validator,
        CatalogImportIngestor $ingestor,
        CatalogImportRewritePlanner $rewritePlanner,
    ): int {
        $manifestPath = (string) $this->argument('manifest');
        try {
            $package = $validator->validate($manifestPath);
            $counts = $package->counts;
            if ((bool) $this->option('dry-run')) {
                $rewritePlan = $rewritePlanner->plan($package);
                $this->info(sprintf(
                    'Validated run=%s sources=%d products=%d memberships=%d images=%d warnings=%d',
                    $package->runId,
                    $counts['sources'],
                    $counts['products'],
                    $counts['memberships'],
                    $counts['images'],
                    $rewritePlan->warningCount,
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
            $correlation = bin2hex(random_bytes(8));
            Log::warning('Catalog import failure', [
                'correlation' => $correlation,
                'exception_class' => $error::class,
                'exception_code' => $error instanceof SafeCatalogImportException
                    ? $error->safeCode()
                    : (string) $error->getCode(),
            ]);
            $this->error(sprintf(
                'Manifest %s: %s correlation=%s',
                $manifestPath,
                $this->safeErrorMessage($error),
                $correlation,
            ));

            return self::FAILURE;
        }
    }

    private function safeErrorMessage(Throwable $error): string
    {
        if ($error instanceof InvalidArgumentException
            && str_starts_with($error->getMessage(), 'Catalog import manifest invariant failed:')) {
            return $error->getMessage();
        }
        if ($error instanceof SafeCatalogImportException) {
            return $error->getMessage();
        }

        return 'Catalog import failed safely; no private diagnostic details were displayed.';
    }
}
