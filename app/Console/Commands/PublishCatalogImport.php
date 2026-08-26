<?php

namespace App\Console\Commands;

use App\Services\CatalogImport\Publication\CatalogImportPublicationException;
use App\Services\CatalogImport\Publication\CatalogImportPublisher;
use Throwable;

final class PublishCatalogImport extends CatalogImportCommand
{
    protected $signature = 'catalog-import:publish
        {run : External run id or database id}
        {--acknowledge-warnings : Bind an explicit acknowledgement to the current exact warning set}
        {--acknowledged-by= : Operator recorded with the warning acknowledgement}';

    protected $description = 'Create a verified backup and publish one fully reviewed catalog import run';

    public function handle(CatalogImportPublisher $publisher): int
    {
        try {
            if (config('catalog-import-publication.enabled') !== true) {
                throw new CatalogImportPublicationException(
                    'Catalog publication is disabled; set RIMSKIE_IMPORT_PUBLICATION_ENABLED=true explicitly.'
                );
            }
            $run = $this->resolveRun();
            $acknowledge = (bool) $this->option('acknowledge-warnings');
            $operator = $this->option('acknowledged-by');
            if ($acknowledge) {
                $operator = is_string($operator) ? $operator : '';
            } elseif ($operator !== null) {
                throw new CatalogImportPublicationException(
                    'Use --acknowledge-warnings together with --acknowledged-by.'
                );
            }

            $result = $publisher->publish($run, $acknowledge ? $operator : null);
            $published = $run->fresh();
            $this->info(sprintf(
                'Published run=%s no_op=%s sitemap=%s backup=%s',
                $run->external_run_id,
                $result->noOp ? 'yes' : 'no',
                $result->sitemapGenerated ? 'generated' : 'retry_required',
                is_string($published?->backup_path) ? $published->backup_path : 'missing',
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
