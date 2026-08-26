<?php

namespace App\Console\Commands;

use App\Services\CatalogImport\Publication\CatalogImportRollback;
use Throwable;

final class RollbackCatalogImport extends CatalogImportCommand
{
    protected $signature = 'catalog-import:rollback
        {run : External run id or database id}';

    protected $description = 'Safely roll back catalog rows and media owned by one published import run';

    public function handle(CatalogImportRollback $rollback): int
    {
        try {
            $run = $this->resolveRun();
            $result = $rollback->rollback($run);
            $this->info(sprintf(
                'Rolled back run=%s no_op=%s sitemap=%s',
                $run->external_run_id,
                $result->noOp ? 'yes' : 'no',
                $result->sitemapGenerated ? 'generated' : 'retry_required',
            ));
            if ($result->diagnostic !== null) {
                $this->warn('Diagnostic: '.$result->diagnostic);
            }

            return self::SUCCESS;
        } catch (Throwable $error) {
            return $this->reportFailure($error);
        }
    }
}
