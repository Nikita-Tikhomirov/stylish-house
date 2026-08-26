<?php

namespace App\Console\Commands;

use App\Models\CatalogImportRun;
use App\Services\CatalogImport\Publication\CatalogImportPublicationException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

abstract class CatalogImportCommand extends Command
{
    protected function resolveRun(?string $providedIdentity = null): CatalogImportRun
    {
        $identity = trim($providedIdentity ?? (string) $this->argument('run'));
        $run = CatalogImportRun::query()->where('external_run_id', $identity)->first();
        if ($run === null && ctype_digit($identity)) {
            $run = CatalogImportRun::query()->find((int) $identity);
        }
        if ($run === null) {
            throw new CatalogImportPublicationException('Catalog import run was not found.');
        }

        return $run;
    }

    protected function reportFailure(Throwable $error): int
    {
        if ($error instanceof CatalogImportPublicationException) {
            $this->error($error->getMessage());

            return self::FAILURE;
        }

        $correlation = bin2hex(random_bytes(8));
        Log::error('Controlled catalog import command failed', [
            'correlation' => $correlation,
            'exception_class' => $error::class,
            'exception_code' => (string) $error->getCode(),
        ]);
        $this->error('Catalog import operation failed safely; correlation='.$correlation);

        return self::FAILURE;
    }
}
