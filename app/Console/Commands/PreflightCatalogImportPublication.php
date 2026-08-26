<?php

namespace App\Console\Commands;

use App\Services\CatalogImport\Publication\CatalogImportPublicationPreflight;
use Throwable;

final class PreflightCatalogImportPublication extends CatalogImportCommand
{
    protected $signature = 'catalog-import:preflight
        {run : External run id or database id}';

    protected $description = 'Verify that a reviewed catalog import run is safe to publish';

    public function handle(CatalogImportPublicationPreflight $preflight): int
    {
        try {
            $run = $this->resolveRun();
            $report = $preflight->inspect($run);
            $this->info(sprintf(
                'Preflight passed run=%s sources=%d products=%d memberships=%d public_attributes=%d warnings=%d',
                $run->external_run_id,
                $report->sourceCount,
                $report->itemCount,
                $report->membershipCount,
                $report->publicAttributeMembershipCount,
                $report->warningCount,
            ));

            return self::SUCCESS;
        } catch (Throwable $error) {
            return $this->reportFailure($error);
        }
    }
}
