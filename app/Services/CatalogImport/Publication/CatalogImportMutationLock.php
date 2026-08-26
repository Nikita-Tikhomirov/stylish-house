<?php

namespace App\Services\CatalogImport\Publication;

use Closure;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;

final class CatalogImportMutationLock
{
    public function synchronized(Closure $callback): mixed
    {
        $name = (string) config(
            'catalog-import-publication.lock_name',
            'catalog-import:publication-mutation',
        );
        $waitSeconds = max(0, (int) config('catalog-import-publication.lock_wait_seconds', 5));

        try {
            return Cache::lock($name, 3600)->block($waitSeconds, $callback);
        } catch (LockTimeoutException) {
            throw new CatalogImportPublicationException(
                'Catalog import publication lock is held by another mutation.'
            );
        }
    }
}
