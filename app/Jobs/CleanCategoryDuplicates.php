<?php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use App\Models\Product;

class CleanCategoryDuplicates implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $categoryId;

    public function __construct($categoryId)
    {
        $this->categoryId = $categoryId;
    }

    public function handle()
    {
       $categoryId = $this->argument('categoryId');

        $this->info('Начинаем очистку дубликатов в категории: ' . $categoryId);

        $uniqueH1s = Product::where('category_id', $categoryId)
                             ->select(DB::raw('TRIM(h1) AS trimmed_h1'))
                             ->groupBy(DB::raw('TRIM(h1)')) // Исправление для only_full_group_by
                             ->pluck('trimmed_h1')
                             ->toArray();

        $this->info('Найдено ' . count($uniqueH1s) . ' уникальных H1.');

        foreach ($uniqueH1s as $h1) {
            $this->info('Обрабатываем H1: ' . $h1);

            Product::where('category_id', $categoryId)
                   ->where(DB::raw('TRIM(h1)'), $h1)
                   ->chunk(200, function ($products) use ($h1, $categoryId) {
                       if (count($products) > 1) {
                           $this->info('Найдено ' . count($products) . ' дубликатов для H1: ' . $h1);

                           // Оставляем первый товар, остальные удаляем
                           $firstProduct = $products->first();
                           $productIdsToDelete = $products->slice(1)->pluck('id')->toArray();

                           Product::whereIn('id', $productIdsToDelete)->delete();

                           $this->info('Удалены дубликаты с ID: ' . implode(', ', $productIdsToDelete));
                       } else {
                           $this->info('Нет дубликатов для H1: ' . $h1);
                       }
                   });
        }

        $this->info('Очистка категории ' . $categoryId . ' завершена! ✨');
    }
}