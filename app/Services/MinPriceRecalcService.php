<?php

namespace App\Services;

use App\Models\PriceRecalcRun;
use App\Models\Product;
use Carbon\Carbon;

class MinPriceRecalcService
{
    public function __construct(
        private readonly ProductMinPriceCalculator $calculator
    ) {
    }

    /**
     * @param array{category_id?:int|null,subcategory_id?:int|null,model_ids?:array<int>|null} $filters
     */
    public function startRun(array $filters, int $batchSize): PriceRecalcRun
    {
        PriceRecalcRun::where('status', PriceRecalcRun::STATUS_RUNNING)
            ->update(['status' => PriceRecalcRun::STATUS_PAUSED]);

        return PriceRecalcRun::create([
            'status' => PriceRecalcRun::STATUS_RUNNING,
            'category_id' => $filters['category_id'] ?? null,
            'subcategory_id' => $filters['subcategory_id'] ?? null,
            'model_ids' => $filters['model_ids'] ?? [],
            'batch_size' => $batchSize,
            'last_product_id' => 0,
            'processed' => 0,
            'updated' => 0,
            'skipped' => 0,
            'started_at' => Carbon::now(),
            'finished_at' => null,
        ]);
    }

    /**
     * @return array{
     * done:bool,
     * processed:int,
     * updated:int,
     * skipped:int,
     * last_product_id:int,
     * errors:array<int,array{product_id:int,error:string,title:string}>
     * }
     */
    public function processNextBatch(PriceRecalcRun $run): array
    {
        if ($run->status !== PriceRecalcRun::STATUS_RUNNING) {
            return [
                'done' => false,
                'processed' => 0,
                'updated' => 0,
                'skipped' => 0,
                'last_product_id' => (int) $run->last_product_id,
                'errors' => [],
            ];
        }

        $query = Product::query()
            ->with('model:id,title')
            ->where('id', '>', $run->last_product_id)
            ->orderBy('id');

        $this->applyFilters($query, $run);

        $products = $query->limit($run->batch_size)->get();

        if ($products->isEmpty()) {
            $run->status = PriceRecalcRun::STATUS_DONE;
            $run->finished_at = Carbon::now();
            $run->save();

            return [
                'done' => true,
                'processed' => 0,
                'updated' => 0,
                'skipped' => 0,
                'last_product_id' => (int) $run->last_product_id,
                'errors' => [],
            ];
        }

        $processed = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        foreach ($products as $product) {
            $processed++;
            $error = null;

            if (empty($product->min_width) || empty($product->min_height)) {
                $error = ProductMinPriceCalculator::ERROR_INVALID_DIMENSIONS;
            } elseif (!$product->model || empty($product->model->title)) {
                $error = ProductMinPriceCalculator::ERROR_SHEET_NOT_FOUND;
            }

            if ($error === null) {
                $result = $this->calculator->calculate([
                    'model' => $product->model->title,
                    'cloth' => $product->cloth,
                    'control' => false,
                    'modelId' => $product->model_id,
                    'prodTitle' => $product->h1,
                    'width' => $product->min_width,
                    'height' => $product->min_height,
                ]);

                if ($result['price'] !== null) {
                    $product->min_price = $result['price'];
                    $product->min_price_updated_at = Carbon::now();
                    $product->min_price_error = null;
                    $product->save();
                    $updated++;
                    continue;
                }

                $error = $result['error'] ?? ProductMinPriceCalculator::ERROR_PRICE_NOT_FOUND;
            }

            $product->min_price_error = $error;
            $product->save();
            $skipped++;
            $errors[] = [
                'product_id' => $product->id,
                'error' => $error,
                'title' => (string) $product->h1,
            ];
        }

        $lastProductId = (int) $products->last()->id;
        $run->last_product_id = $lastProductId;
        $run->processed += $processed;
        $run->updated += $updated;
        $run->skipped += $skipped;

        $hasMoreQuery = Product::query()
            ->where('id', '>', $lastProductId);
        $this->applyFilters($hasMoreQuery, $run);
        $hasMore = $hasMoreQuery->exists();

        if (!$hasMore) {
            $run->status = PriceRecalcRun::STATUS_DONE;
            $run->finished_at = Carbon::now();
        }

        $run->save();

        return [
            'done' => !$hasMore,
            'processed' => $processed,
            'updated' => $updated,
            'skipped' => $skipped,
            'last_product_id' => $lastProductId,
            'errors' => array_slice($errors, -20),
        ];
    }

    private function applyFilters($query, PriceRecalcRun $run): void
    {
        if (!empty($run->category_id)) {
            $query->where('category_id', $run->category_id);
        }

        if (!empty($run->subcategory_id)) {
            $query->where('subcategory_id', $run->subcategory_id);
        }

        $modelIds = is_array($run->model_ids) ? $run->model_ids : [];
        if (!empty($modelIds)) {
            $query->whereIn('model_id', $modelIds);
        }
    }
}
