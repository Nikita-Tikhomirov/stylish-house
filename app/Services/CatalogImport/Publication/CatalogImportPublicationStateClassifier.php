<?php

namespace App\Services\CatalogImport\Publication;

use App\Models\CatalogImportRun;
use Illuminate\Support\Facades\DB;

class CatalogImportPublicationStateClassifier
{
    public function classify(CatalogImportRun $run): string
    {
        $current = $run->fresh();
        if ($current === null) {
            return 'uncertain';
        }
        $sourceCount = $current->sources()->count();
        $itemCount = $current->items()->count();
        $sourcePointers = $current->sources()->whereNotNull('published_subcategory_id')->count();
        $sourceSnapshots = $current->sources()->whereNotNull('publication_snapshot')->count();
        $itemPointers = $current->items()->whereNotNull('published_product_id')->count();
        $itemSnapshots = $current->items()->whereNotNull('publication_snapshot')->count();
        $ownedProducts = DB::table('products')->where('import_run_id', $current->id)->count();
        $ownedSubcategories = DB::table('subcategories')->where('import_run_id', $current->id)->count();
        $ownedCollectionPivots = DB::table('catalog_collection_product')
            ->where('catalog_import_run_id', $current->id)
            ->count();
        $ownedAttributePivots = DB::table('catalog_product_attribute_value')
            ->where('catalog_import_run_id', $current->id)
            ->count();

        if ($current->status === CatalogImportRun::STATUS_PUBLISHED
            && $sourceCount === 46 && $sourcePointers === 46 && $sourceSnapshots === 46
            && $itemCount >= 1 && $itemCount === $current->unique_product_count
            && $itemPointers === $itemCount && $itemSnapshots === $itemCount
            && $ownedProducts === $itemCount && $ownedSubcategories === $sourceCount) {
            return 'committed';
        }
        if (in_array($current->status, [CatalogImportRun::STATUS_STAGED, CatalogImportRun::STATUS_REVIEWING], true)
            && $sourcePointers === 0 && $sourceSnapshots === 0
            && $itemPointers === 0 && $itemSnapshots === 0
            && $ownedProducts === 0 && $ownedSubcategories === 0
            && $ownedCollectionPivots === 0 && $ownedAttributePivots === 0) {
            return 'uncommitted';
        }

        return 'uncertain';
    }
}
