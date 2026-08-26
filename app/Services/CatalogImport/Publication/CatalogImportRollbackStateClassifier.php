<?php

namespace App\Services\CatalogImport\Publication;

use App\Models\CatalogImportRun;
use Illuminate\Support\Facades\DB;

class CatalogImportRollbackStateClassifier
{
    /**
     * @param  array<int, int>  $productIds
     * @param  array<int, int>  $subcategoryIds
     */
    public function classify(CatalogImportRun $run, array $productIds, array $subcategoryIds): string
    {
        $current = $run->fresh();
        if ($current === null) {
            return 'uncertain';
        }
        $ownedProducts = DB::table('products')
            ->whereIn('id', $productIds)
            ->where('import_run_id', $run->id)
            ->count();
        $ownedSubcategories = DB::table('subcategories')
            ->whereIn('id', $subcategoryIds)
            ->where('import_run_id', $run->id)
            ->count();
        $ownedCollectionPivots = DB::table('catalog_collection_product')
            ->where('catalog_import_run_id', $run->id)
            ->count();
        $ownedAttributePivots = DB::table('catalog_product_attribute_value')
            ->where('catalog_import_run_id', $run->id)
            ->count();
        $publishedSourcePointers = $run->sources()->whereNotNull('published_subcategory_id')->count();
        $publishedItemPointers = $run->items()->whereNotNull('published_product_id')->count();
        if ($current->status === CatalogImportRun::STATUS_ROLLED_BACK
            && $ownedProducts === 0 && $ownedSubcategories === 0
            && $ownedCollectionPivots === 0 && $ownedAttributePivots === 0
            && $publishedSourcePointers === 0 && $publishedItemPointers === 0) {
            return 'committed';
        }
        if ($current->status === CatalogImportRun::STATUS_PUBLISHED
            && $ownedProducts === count($productIds)
            && $ownedSubcategories === count($subcategoryIds)
            && $publishedSourcePointers === $run->sources()->count()
            && $publishedItemPointers === $run->items()->count()) {
            return 'published';
        }

        return 'uncertain';
    }
}
