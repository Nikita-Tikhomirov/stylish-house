<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\PriceRecalcRun;
use App\Models\ProdModel;
use App\Models\Subcategory;
use App\Services\MinPriceRecalcService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MinPriceRunController extends Controller
{
    public function __construct(
        private readonly MinPriceRecalcService $service
    ) {
    }

    public function page()
    {
        $categories = Category::query()->orderBy('titleh1')->get(['id', 'titleh1']);
        $subcategories = Subcategory::query()->orderBy('titleh1')->get(['id', 'category_id', 'titleh1']);
        $models = ProdModel::query()->orderBy('title')->get(['id', 'h1', 'title']);
        $activeRun = $this->service->getActiveRun();

        return view('admin.min-price-generator', compact('categories', 'subcategories', 'models', 'activeRun'));
    }

    public function start(Request $request): JsonResponse
    {
        $data = $request->validate([
            'category_id' => 'nullable|integer|exists:categories,id',
            'subcategory_id' => 'nullable|integer|exists:subcategories,id',
            'model_ids' => 'nullable|array',
            'model_ids.*' => 'integer|exists:prod_model,id',
            'batch_size' => 'nullable|integer|min:25|max:1000',
            'mode' => 'nullable|string|in:auto,manual',
            'start_id' => 'nullable|integer|min:1',
            'end_id' => 'nullable|integer|min:1',
            'skip_filled' => 'nullable|boolean',
            'overwrite_existing' => 'nullable|boolean',
        ]);

        try {
            $run = $this->service->startRun([
                'category_id' => $data['category_id'] ?? null,
                'subcategory_id' => $data['subcategory_id'] ?? null,
                'model_ids' => $data['model_ids'] ?? [],
                'mode' => $data['mode'] ?? PriceRecalcRun::MODE_MANUAL,
                'start_id' => $data['start_id'] ?? null,
                'end_id' => $data['end_id'] ?? null,
                'skip_filled' => (bool) ($data['skip_filled'] ?? true),
                'overwrite_existing' => (bool) ($data['overwrite_existing'] ?? false),
            ], (int) ($data['batch_size'] ?? 200));
        } catch (\RuntimeException $exception) {
            $activeRunId = $exception->getMessage();
            return response()->json([
                'message' => 'Есть активный запуск. Откройте его или остановите перед стартом нового.',
                'active_run_id' => (int) $activeRunId,
            ], 409);
        }

        return response()->json([
            'message' => 'Запуск создан',
            'run' => $run,
        ]);
    }

    public function next(Request $request): JsonResponse
    {
        $run = $this->resolveRun($request);
        $batch = $this->service->processNextBatch($run);
        $state = $this->service->getRunState($run);

        return response()->json([
            'message' => $batch['done'] ? 'Пересчет завершен' : 'Пакет обработан',
            'batch' => $batch,
            'state' => $state,
        ]);
    }

    public function pause(Request $request): JsonResponse
    {
        $run = $this->resolveRun($request);
        $run = $this->service->pauseRun($run);

        return response()->json([
            'message' => 'Запуск поставлен на паузу',
            'run' => $run,
        ]);
    }

    public function resume(Request $request): JsonResponse
    {
        $run = $this->resolveRun($request);
        $run = $this->service->resumeRun($run);

        return response()->json([
            'message' => 'Запуск продолжен',
            'run' => $run,
        ]);
    }

    public function stop(Request $request): JsonResponse
    {
        $data = $request->validate([
            'run_id' => 'required|integer|exists:price_recalc_runs,id',
            'reason' => 'nullable|string|max:255',
        ]);

        $run = PriceRecalcRun::query()->findOrFail($data['run_id']);
        $run = $this->service->stopRun($run, $data['reason'] ?? null);

        return response()->json([
            'message' => 'Запуск остановлен',
            'run' => $run,
        ]);
    }

    public function state(Request $request): JsonResponse
    {
        $data = $request->validate([
            'run_id' => 'required|integer|exists:price_recalc_runs,id',
        ]);

        $run = PriceRecalcRun::query()->findOrFail($data['run_id']);
        $state = $this->service->getRunState($run);

        return response()->json($state);
    }

    public function runs(): JsonResponse
    {
        return response()->json([
            'runs' => $this->service->listRuns(),
        ]);
    }

    public function results(Request $request): JsonResponse
    {
        $data = $request->validate([
            'run_id' => 'required|integer|exists:price_recalc_runs,id',
            'status' => 'nullable|string|in:updated,skipped,error',
            'q' => 'nullable|string|max:255',
            'per_page' => 'nullable|integer|min:10|max:200',
        ]);

        $run = PriceRecalcRun::query()->findOrFail($data['run_id']);
        $items = $this->service->listRunItems(
            $run,
            $data['status'] ?? null,
            $data['q'] ?? null,
            (int) ($data['per_page'] ?? 50),
        );

        return response()->json([
            'items' => $items->items(),
            'pagination' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $data = $request->validate([
            'run_id' => 'required|integer|exists:price_recalc_runs,id',
            'status' => 'nullable|string|in:updated,skipped,error',
        ]);

        $run = PriceRecalcRun::query()->findOrFail($data['run_id']);
        $rows = $this->service->buildExportRows($run, $data['status'] ?? null);
        $filename = 'min-price-run-' . $run->id . '-' . Str::slug($run->status) . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'product_id',
                'product_title',
                'status',
                'old_min_price',
                'new_min_price',
                'error_code',
                'error_message',
                'processed_at',
            ]);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row->product_id,
                    $row->product_title,
                    $row->status,
                    $row->old_min_price,
                    $row->new_min_price,
                    $row->error_code,
                    $row->error_message,
                    $row->processed_at,
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function sizesPreview(Request $request): JsonResponse
    {
        $data = $request->validate([
            'category_id' => 'nullable|integer|exists:categories,id',
            'subcategory_id' => 'nullable|integer|exists:subcategories,id',
            'model_ids' => 'nullable|array',
            'model_ids.*' => 'integer|exists:prod_model,id',
            'start_id' => 'nullable|integer|min:1',
            'end_id' => 'nullable|integer|min:1',
        ]);

        return response()->json([
            'matched' => $this->service->countSizeCandidates($this->extractFilterPayload($data)),
        ]);
    }

    public function sizesUpdate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'category_id' => 'nullable|integer|exists:categories,id',
            'subcategory_id' => 'nullable|integer|exists:subcategories,id',
            'model_ids' => 'nullable|array',
            'model_ids.*' => 'integer|exists:prod_model,id',
            'start_id' => 'nullable|integer|min:1',
            'end_id' => 'nullable|integer|min:1',
            'min_width' => 'nullable|integer|min:1|required_without:min_height',
            'min_height' => 'nullable|integer|min:1|required_without:min_width',
            'write_mode' => 'required|string|in:overwrite,skip_filled',
        ]);

        $result = $this->service->applyMinSizes(
            $this->extractFilterPayload($data),
            [
                'min_width' => $data['min_width'] ?? null,
                'min_height' => $data['min_height'] ?? null,
            ],
            $data['write_mode']
        );

        return response()->json($result);
    }

    private function resolveRun(Request $request): PriceRecalcRun
    {
        $data = $request->validate([
            'run_id' => 'required|integer|exists:price_recalc_runs,id',
        ]);

        return PriceRecalcRun::query()->findOrFail($data['run_id']);
    }

    /**
     * @param array{
     * category_id?:int|null,
     * subcategory_id?:int|null,
     * model_ids?:array<int>|null,
     * start_id?:int|null,
     * end_id?:int|null
     * } $data
     * @return array{
     * category_id:int|null,
     * subcategory_id:int|null,
     * model_ids:array<int>,
     * start_id:int|null,
     * end_id:int|null
     * }
     */
    private function extractFilterPayload(array $data): array
    {
        return [
            'category_id' => $data['category_id'] ?? null,
            'subcategory_id' => $data['subcategory_id'] ?? null,
            'model_ids' => $data['model_ids'] ?? [],
            'start_id' => $data['start_id'] ?? null,
            'end_id' => $data['end_id'] ?? null,
        ];
    }
}
