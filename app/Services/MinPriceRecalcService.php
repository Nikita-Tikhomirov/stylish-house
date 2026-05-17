<?php

namespace App\Services;

use App\Models\PriceRecalcRun;
use App\Models\PriceRecalcRunItem;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use RuntimeException;

class MinPriceRecalcService
{
    private const SIZE_WRITE_MODE_OVERWRITE = 'overwrite';
    private const SIZE_WRITE_MODE_SKIP_FILLED = 'skip_filled';

    public function __construct(
        private readonly ProductMinPriceCalculator $calculator
    ) {
    }

    /**
     * @param array{
     * category_id?:int|null,
     * subcategory_id?:int|null,
     * model_ids?:array<int>,
     * mode?:string,
     * start_id?:int|null,
     * end_id?:int|null,
     * skip_filled?:bool,
     * overwrite_existing?:bool
     * } $params
     */
    public function startRun(array $params, int $batchSize): PriceRecalcRun
    {
        $activeRun = $this->getActiveRun();
        if ($activeRun !== null) {
            throw new RuntimeException((string) $activeRun->id);
        }

        $batchSize = max(25, min(1000, $batchSize));
        $startId = $params['start_id'] ?? null;
        $endId = $params['end_id'] ?? null;
        if ($startId !== null && $endId !== null && $startId > $endId) {
            [$startId, $endId] = [$endId, $startId];
        }

        $mode = in_array($params['mode'] ?? PriceRecalcRun::MODE_MANUAL, [PriceRecalcRun::MODE_AUTO, PriceRecalcRun::MODE_MANUAL], true)
            ? $params['mode']
            : PriceRecalcRun::MODE_MANUAL;

        $run = PriceRecalcRun::create([
            'status' => PriceRecalcRun::STATUS_RUNNING,
            'mode' => $mode,
            'category_id' => $params['category_id'] ?? null,
            'subcategory_id' => $params['subcategory_id'] ?? null,
            'model_ids' => $params['model_ids'] ?? [],
            'batch_size' => $batchSize,
            'start_id' => $startId,
            'end_id' => $endId,
            'current_id' => ($startId !== null ? max(0, $startId - 1) : 0),
            'skip_filled' => (bool) ($params['skip_filled'] ?? true),
            'overwrite_existing' => (bool) ($params['overwrite_existing'] ?? false),
            'last_product_id' => 0,
            'processed' => 0,
            'updated' => 0,
            'skipped' => 0,
            'total_candidates' => null,
            'progress_percent' => 0,
            'eta_seconds' => null,
            'stop_reason' => null,
            'started_at' => Carbon::now(),
            'finished_at' => null,
        ]);

        $run->total_candidates = $this->buildProductsQuery($run, false)->count();
        $run->save();

        return $run;
    }

    public function pauseRun(PriceRecalcRun $run): PriceRecalcRun
    {
        if ($run->status === PriceRecalcRun::STATUS_RUNNING) {
            $run->status = PriceRecalcRun::STATUS_PAUSED;
            $run->save();
        }
        return $run->fresh();
    }

    public function resumeRun(PriceRecalcRun $run): PriceRecalcRun
    {
        if ($run->status === PriceRecalcRun::STATUS_PAUSED) {
            $run->status = PriceRecalcRun::STATUS_RUNNING;
            $run->save();
        }
        return $run->fresh();
    }

    public function stopRun(PriceRecalcRun $run, ?string $reason = null): PriceRecalcRun
    {
        if (in_array($run->status, [PriceRecalcRun::STATUS_RUNNING, PriceRecalcRun::STATUS_PAUSED], true)) {
            $run->status = PriceRecalcRun::STATUS_STOPPED;
            $run->stop_reason = $reason ?: 'stopped_by_operator';
            $run->finished_at = Carbon::now();
            $this->refreshProgressMetrics($run);
            $run->save();
        }
        return $run->fresh();
    }

    /**
     * @return array{
     * done:bool,
     * processed:int,
     * updated:int,
     * skipped:int,
     * current_id:int,
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
                'current_id' => (int) $run->current_id,
                'errors' => [],
            ];
        }

        $products = $this->buildProductsQuery($run, true)
            ->limit($run->batch_size)
            ->get();

        if ($products->isEmpty()) {
            $run->status = PriceRecalcRun::STATUS_DONE;
            $run->finished_at = Carbon::now();
            $this->refreshProgressMetrics($run);
            $run->save();

            return [
                'done' => true,
                'processed' => 0,
                'updated' => 0,
                'skipped' => 0,
                'current_id' => (int) $run->current_id,
                'errors' => [],
            ];
        }

        $processed = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];
        $now = Carbon::now();

        foreach ($products as $product) {
            $processed++;
            $oldPrice = $product->min_price;
            $newPrice = null;
            $status = PriceRecalcRunItem::STATUS_SKIPPED;
            $errorCode = null;
            $errorMessage = null;
            $productModel = $product->getRelationValue('model');
            $modelTitle = trim((string) ($productModel?->title ?? ''));

            if ($run->skip_filled && !$run->overwrite_existing && $product->min_price !== null) {
                $errorCode = 'already_filled';
                $errorMessage = 'Min price already set';
            } elseif (empty($product->min_width) || empty($product->min_height)) {
                $errorCode = ProductMinPriceCalculator::ERROR_INVALID_DIMENSIONS;
                $errorMessage = 'Missing min dimensions';
            } elseif ($modelTitle === '' && !$this->isSantehRolletsProduct($product)) {
                $errorCode = ProductMinPriceCalculator::ERROR_SHEET_NOT_FOUND;
                $errorMessage = 'Model title not found';
            } else {
                $result = $this->calculator->calculate([
                    'model' => $modelTitle,
                    'cloth' => $product->cloth,
                    'control' => false,
                    'modelId' => $product->model_id,
                    'prodTitle' => $product->h1,
                    'width' => $product->min_width,
                    'height' => $product->min_height,
                ]);

                if ($result['price'] !== null) {
                    $product->min_price = $result['price'];
                    $product->min_price_updated_at = $now;
                    $product->min_price_error = null;
                    $product->save();

                    $newPrice = (int) $result['price'];
                    $status = PriceRecalcRunItem::STATUS_UPDATED;
                    $updated++;
                } else {
                    $errorCode = $result['error'] ?? ProductMinPriceCalculator::ERROR_PRICE_NOT_FOUND;
                    $errorMessage = 'Price not found';
                    $product->min_price_error = $errorCode;
                    $product->save();
                }
            }

            if ($status !== PriceRecalcRunItem::STATUS_UPDATED) {
                if ($errorCode && !in_array($errorCode, ['already_filled'], true)) {
                    $status = PriceRecalcRunItem::STATUS_ERROR;
                    $errors[] = [
                        'product_id' => (int) $product->id,
                        'error' => $errorCode,
                        'title' => (string) $product->h1,
                    ];
                }
                $skipped++;
            }

            PriceRecalcRunItem::create([
                'run_id' => $run->id,
                'product_id' => $product->id,
                'status' => $status,
                'old_min_price' => $oldPrice !== null ? (int) $oldPrice : null,
                'new_min_price' => $newPrice,
                'error_code' => $errorCode,
                'error_message' => $errorMessage,
                'processed_at' => $now,
            ]);
        }

        $lastProductId = (int) $products->last()->id;
        $run->current_id = $lastProductId;
        $run->last_product_id = $lastProductId;
        $run->processed += $processed;
        $run->updated += $updated;
        $run->skipped += $skipped;

        $hasMore = $this->buildProductsQuery($run, true)->exists();
        if (!$hasMore) {
            $run->status = PriceRecalcRun::STATUS_DONE;
            $run->finished_at = Carbon::now();
        }

        $this->refreshProgressMetrics($run);
        $run->save();

        return [
            'done' => !$hasMore,
            'processed' => $processed,
            'updated' => $updated,
            'skipped' => $skipped,
            'current_id' => $lastProductId,
            'errors' => array_slice($errors, -20),
        ];
    }

    private function isSantehRolletsProduct(Product $product): bool
    {
        $title = mb_strtolower(trim((string) $product->h1 . ' ' . (string) $product->title));

        return str_contains($title, 'сантехнические роллеты')
            || str_contains($title, 'сантехнические рольставни');
    }

    public function getActiveRun(): ?PriceRecalcRun
    {
        return PriceRecalcRun::query()
            ->whereIn('status', [PriceRecalcRun::STATUS_RUNNING, PriceRecalcRun::STATUS_PAUSED])
            ->latest('id')
            ->first();
    }

    public function getRunState(PriceRecalcRun $run): array
    {
        $run->refresh();
        $this->refreshProgressMetrics($run);

        $range = PriceRecalcRunItem::query()
            ->where('run_id', $run->id)
            ->selectRaw('MIN(product_id) as min_id, MAX(product_id) as max_id')
            ->first();

        $lastErrors = PriceRecalcRunItem::query()
            ->where('run_id', $run->id)
            ->where('status', PriceRecalcRunItem::STATUS_ERROR)
            ->orderByDesc('id')
            ->limit(10)
            ->get([
                'product_id',
                'error_code',
                'error_message',
                'processed_at',
            ]);

        return [
            'run' => $run,
            'range_processed' => [
                'from' => $range?->min_id ? (int) $range->min_id : null,
                'to' => $range?->max_id ? (int) $range->max_id : null,
            ],
            'last_errors' => $lastErrors,
        ];
    }

    public function listRuns(int $limit = 30): Collection
    {
        return PriceRecalcRun::query()
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function listRunItems(PriceRecalcRun $run, ?string $status, ?string $query, int $perPage = 50): LengthAwarePaginator
    {
        $builder = PriceRecalcRunItem::query()
            ->where('run_id', $run->id)
            ->leftJoin('products', 'products.id', '=', 'price_recalc_run_items.product_id')
            ->select([
                'price_recalc_run_items.*',
                'products.h1 as product_title',
            ])
            ->orderByDesc('id')
            ->with(['run']);

        if ($status && in_array($status, [
            PriceRecalcRunItem::STATUS_UPDATED,
            PriceRecalcRunItem::STATUS_SKIPPED,
            PriceRecalcRunItem::STATUS_ERROR,
        ], true)) {
            $builder->where('status', $status);
        }

        if ($query !== null && trim($query) !== '') {
            $query = trim($query);
            $builder->where(function (Builder $q) use ($query) {
                if (is_numeric($query)) {
                    $q->orWhere('price_recalc_run_items.product_id', (int) $query);
                }
                $q->orWhere('products.h1', 'like', '%' . $query . '%');
            });
        }

        return $builder->paginate($perPage);
    }

    public function buildExportRows(PriceRecalcRun $run, ?string $status): Collection
    {
        $builder = PriceRecalcRunItem::query()
            ->where('run_id', $run->id)
            ->leftJoin('products', 'products.id', '=', 'price_recalc_run_items.product_id')
            ->select([
                'price_recalc_run_items.product_id',
                'products.h1 as product_title',
                'price_recalc_run_items.status',
                'price_recalc_run_items.old_min_price',
                'price_recalc_run_items.new_min_price',
                'price_recalc_run_items.error_code',
                'price_recalc_run_items.error_message',
                'price_recalc_run_items.processed_at',
            ])
            ->orderBy('price_recalc_run_items.id');

        if ($status && in_array($status, [
            PriceRecalcRunItem::STATUS_UPDATED,
            PriceRecalcRunItem::STATUS_SKIPPED,
            PriceRecalcRunItem::STATUS_ERROR,
        ], true)) {
            $builder->where('price_recalc_run_items.status', $status);
        }

        return $builder->get();
    }

    /**
     * @param array{
     * category_id?:int|null,
     * subcategory_id?:int|null,
     * model_ids?:array<int>,
     * start_id?:int|null,
     * end_id?:int|null
     * } $filters
     */
    public function countSizeCandidates(array $filters): int
    {
        $normalizedFilters = $this->normalizeFilters($filters);
        return $this->buildProductsQueryByFilters($normalizedFilters)->count();
    }

    /**
     * @param array{
     * category_id?:int|null,
     * subcategory_id?:int|null,
     * model_ids?:array<int>,
     * start_id?:int|null,
     * end_id?:int|null
     * } $filters
     * @param array{
     * min_width?:int|null,
     * min_height?:int|null
     * } $sizePayload
     * @return array{
     * matched:int,
     * updated:int,
     * skipped:int,
     * range:array{from:int|null,to:int|null}
     * }
     */
    public function applyMinSizes(array $filters, array $sizePayload, string $writeMode): array
    {
        $normalizedFilters = $this->normalizeFilters($filters);
        $baseQuery = $this->buildProductsQueryByFilters($normalizedFilters)->orderBy('id');

        $matched = (clone $baseQuery)->count();
        $rangeRow = (clone $baseQuery)
            ->selectRaw('MIN(id) as from_id, MAX(id) as to_id')
            ->first();

        if ($matched === 0) {
            return [
                'matched' => 0,
                'updated' => 0,
                'skipped' => 0,
                'range' => [
                    'from' => null,
                    'to' => null,
                ],
            ];
        }

        $writeMode = in_array($writeMode, [self::SIZE_WRITE_MODE_OVERWRITE, self::SIZE_WRITE_MODE_SKIP_FILLED], true)
            ? $writeMode
            : self::SIZE_WRITE_MODE_OVERWRITE;

        $newWidth = $sizePayload['min_width'] ?? null;
        $newHeight = $sizePayload['min_height'] ?? null;
        $products = (clone $baseQuery)->get(['id', 'min_width', 'min_height']);

        $updated = 0;
        $skipped = 0;

        foreach ($products as $product) {
            $attributesToWrite = [];

            if ($newWidth !== null) {
                if ($writeMode === self::SIZE_WRITE_MODE_OVERWRITE || $this->isEmptyMinSize($product->min_width)) {
                    $attributesToWrite['min_width'] = $newWidth;
                }
            }

            if ($newHeight !== null) {
                if ($writeMode === self::SIZE_WRITE_MODE_OVERWRITE || $this->isEmptyMinSize($product->min_height)) {
                    $attributesToWrite['min_height'] = $newHeight;
                }
            }

            if (empty($attributesToWrite)) {
                $skipped++;
                continue;
            }

            $product->fill($attributesToWrite);
            $product->save();
            $updated++;
        }

        return [
            'matched' => $matched,
            'updated' => $updated,
            'skipped' => $skipped,
            'range' => [
                'from' => $rangeRow?->from_id !== null ? (int) $rangeRow->from_id : null,
                'to' => $rangeRow?->to_id !== null ? (int) $rangeRow->to_id : null,
            ],
        ];
    }

    private function buildProductsQuery(PriceRecalcRun $run, bool $applyCursor): Builder
    {
        $query = Product::query()
            ->with('model:id,title')
            ->orderBy('id');

        if ($applyCursor) {
            $query->where('id', '>', (int) $run->current_id);
        }

        $filters = $this->normalizeFilters([
            'category_id' => $run->category_id,
            'subcategory_id' => $run->subcategory_id,
            'model_ids' => is_array($run->model_ids) ? $run->model_ids : [],
            'start_id' => $run->start_id,
            'end_id' => $run->end_id,
        ]);

        $this->applyFiltersToQuery($query, $filters);

        return $query;
    }

    /**
     * @param array{
     * category_id?:int|null,
     * subcategory_id?:int|null,
     * model_ids?:array<int>,
     * start_id?:int|null,
     * end_id?:int|null
     * } $filters
     */
    private function buildProductsQueryByFilters(array $filters): Builder
    {
        $query = Product::query()->orderBy('id');
        $this->applyFiltersToQuery($query, $filters);

        return $query;
    }

    /**
     * @param array{
     * category_id:int|null,
     * subcategory_id:int|null,
     * model_ids:array<int>,
     * start_id:int|null,
     * end_id:int|null
     * } $filters
     */
    private function applyFiltersToQuery(Builder $query, array $filters): void
    {
        if (!empty($filters['start_id'])) {
            $query->where('id', '>=', (int) $filters['start_id']);
        }

        if (!empty($filters['end_id'])) {
            $query->where('id', '<=', (int) $filters['end_id']);
        }

        if (!empty($filters['category_id'])) {
            $query->where('category_id', (int) $filters['category_id']);
        }

        if (!empty($filters['subcategory_id'])) {
            $query->where('subcategory_id', (int) $filters['subcategory_id']);
        }

        if (!empty($filters['model_ids'])) {
            $query->whereIn('model_id', $filters['model_ids']);
        }
    }

    /**
     * @param array{
     * category_id?:int|null,
     * subcategory_id?:int|null,
     * model_ids?:array<int>,
     * start_id?:int|null,
     * end_id?:int|null
     * } $filters
     * @return array{
     * category_id:int|null,
     * subcategory_id:int|null,
     * model_ids:array<int>,
     * start_id:int|null,
     * end_id:int|null
     * }
     */
    private function normalizeFilters(array $filters): array
    {
        $startId = isset($filters['start_id']) ? (int) $filters['start_id'] : null;
        $endId = isset($filters['end_id']) ? (int) $filters['end_id'] : null;

        if ($startId !== null && $endId !== null && $startId > $endId) {
            [$startId, $endId] = [$endId, $startId];
        }

        $modelIds = array_values(array_filter(
            array_map(
                static fn ($id): int => (int) $id,
                is_array($filters['model_ids'] ?? null) ? $filters['model_ids'] : []
            ),
            static fn (int $id): bool => $id > 0
        ));

        return [
            'category_id' => isset($filters['category_id']) ? (int) $filters['category_id'] : null,
            'subcategory_id' => isset($filters['subcategory_id']) ? (int) $filters['subcategory_id'] : null,
            'model_ids' => $modelIds,
            'start_id' => $startId,
            'end_id' => $endId,
        ];
    }

    private function isEmptyMinSize($value): bool
    {
        if ($value === null) {
            return true;
        }

        return (int) $value <= 0;
    }

    private function refreshProgressMetrics(PriceRecalcRun $run): void
    {
        $total = $run->total_candidates ?? 0;
        if ($total <= 0) {
            $run->progress_percent = 0;
            $run->eta_seconds = null;
            return;
        }

        $processed = max(0, (int) $run->processed);
        $progress = min(100, round(($processed / $total) * 100, 2));
        $run->progress_percent = $progress;

        if ($run->started_at && $processed > 0 && $progress < 100) {
            $elapsed = max(1, Carbon::now()->diffInSeconds($run->started_at));
            $perItem = $elapsed / $processed;
            $remaining = max(0, $total - $processed);
            $run->eta_seconds = (int) round($remaining * $perItem);
        } else {
            $run->eta_seconds = null;
        }
    }
}
