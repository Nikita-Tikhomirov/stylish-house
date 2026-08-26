<?php

namespace App\Services\CatalogImport\Publication;

use Illuminate\Support\Facades\DB;

class LaravelCatalogImportTransaction
{
    public function begin(): void
    {
        DB::beginTransaction();
    }

    public function commit(): void
    {
        DB::commit();
    }

    public function rollBackIfActive(): void
    {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
    }
}
