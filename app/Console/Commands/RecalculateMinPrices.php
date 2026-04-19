<?php

namespace App\Console\Commands;

use App\Models\PriceRecalcRun;
use App\Services\MinPriceRecalcService;
use Illuminate\Console\Command;

class RecalculateMinPrices extends Command
{
    protected $signature = 'prices:min-recalc
                            {--run= : Existing run id}
                            {--batch=200 : Batch size for a new run}
                            {--category= : Category id filter}
                            {--subcategory= : Subcategory id filter}
                            {--models=* : Model ids filter}
                            {--start-id= : Start product id}
                            {--end-id= : End product id}
                            {--mode=manual : manual|auto}
                            {--skip-filled=1 : 1/0 skip already filled min_price}
                            {--overwrite=0 : 1/0 force overwrite existing min_price}
                            {--steps=1 : Number of next-batch iterations}';

    protected $description = 'Process min price recalculation in batches';

    public function handle(MinPriceRecalcService $service): int
    {
        $runId = $this->option('run');
        $steps = max(1, (int) $this->option('steps'));

        if ($runId) {
            /** @var PriceRecalcRun|null $run */
            $run = PriceRecalcRun::query()->find($runId);
            if (!$run) {
                $this->error('Run not found.');
                return self::FAILURE;
            }
        } else {
            $batchSize = max(50, min(500, (int) $this->option('batch')));
            try {
                $run = $service->startRun([
                    'category_id' => $this->option('category') ? (int) $this->option('category') : null,
                    'subcategory_id' => $this->option('subcategory') ? (int) $this->option('subcategory') : null,
                    'model_ids' => array_map('intval', (array) $this->option('models')),
                    'mode' => (string) $this->option('mode'),
                    'start_id' => $this->option('start-id') ? (int) $this->option('start-id') : null,
                    'end_id' => $this->option('end-id') ? (int) $this->option('end-id') : null,
                    'skip_filled' => (bool) ((int) $this->option('skip-filled')),
                    'overwrite_existing' => (bool) ((int) $this->option('overwrite')),
                ], $batchSize);
            } catch (\RuntimeException $exception) {
                $this->error("Active run exists: #{$exception->getMessage()}");
                return self::FAILURE;
            }

            $this->info("Created run #{$run->id}.");
        }

        for ($i = 0; $i < $steps; $i++) {
            $run->refresh();
            if ($run->status !== PriceRecalcRun::STATUS_RUNNING) {
                $this->warn("Run status is {$run->status}, stopping.");
                break;
            }

            $batch = $service->processNextBatch($run);
            $run->refresh();

            $this->line(sprintf(
                'Step %d: processed=%d updated=%d skipped=%d total_processed=%d status=%s',
                $i + 1,
                $batch['processed'],
                $batch['updated'],
                $batch['skipped'],
                $run->processed,
                $run->status
            ));

            if ($batch['done']) {
                $this->info('Run completed.');
                break;
            }
        }

        return self::SUCCESS;
    }
}
