<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Cache;

class LoadExcelData extends Command
{
    protected $signature = 'excel:load-data';
    protected $description = 'Load Excel data and cache it';
    private const SANTEH_ROLLETS_CACHE_KEY = 'santeh_rollets_price_matrix';

    public function handle()
    {
        $filePath = storage_path('app/public/table1.xls');
        $spreadsheet = IOFactory::load($filePath);

        $cachedSheets = Cache::get('cached_sheets', []); // Получаем уже сохраненные ключи

        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $sheetTitle = trim($sheet->getTitle());
            $sheetData = [];

            foreach ($sheet->getRowIterator() as $row) {
                $rowIndex = $row->getRowIndex();
                $sheetData[$rowIndex] = [];

                foreach ($row->getCellIterator() as $cell) {
                    $columnIndex = $cell->getColumn();
                    $sheetData[$rowIndex][$columnIndex] = $cell->getCalculatedValue();
                }
            }

            // Кэшируем лист
            Cache::forever('sheet_' . $sheetTitle, $sheetData);

            // Добавляем название листа в список
            $cachedSheets[] = 'sheet_' . $sheetTitle;
        }

        Cache::forever('cached_sheets', array_unique($cachedSheets)); // Обновляем кэш со списком листов

        $this->loadSantehRolletsPrices($cachedSheets);

        $this->info('Excel data with calculated values loaded and cached successfully!');
    }

    private function loadSantehRolletsPrices(array $cachedSheets): void
    {
        $filePath = storage_path('app/public/santeh_rollets_prices.xlsx');
        if (!file_exists($filePath)) {
            return;
        }

        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getSheet(0);

        $widths = [];
        $heights = [];
        $prices = [];
        $minWidth = null;
        $minHeight = null;
        $highestColumn = $sheet->getHighestColumn();
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
        $highestRow = $sheet->getHighestRow();

        for ($column = 2; $column <= $highestColumnIndex; $column++) {
            $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($column);
            $value = $sheet->getCell($columnLetter . '2')->getCalculatedValue();
            if (is_numeric($value) && (int) $value > 0) {
                $widths[$column] = (int) $value;
            }
        }

        for ($row = 3; $row <= $highestRow; $row++) {
            $height = $sheet->getCell('A' . $row)->getCalculatedValue();
            if (!is_numeric($height) || (int) $height <= 0) {
                continue;
            }

            $height = (int) $height;
            $heights[$row] = $height;

            foreach ($widths as $column => $width) {
                $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($column);
                $price = $sheet->getCell($columnLetter . $row)->getCalculatedValue();
                if (!is_numeric($price)) {
                    continue;
                }

                $price = (float) $price;
                $prices[$height][$width] = $price;
            }
        }

        $prices = $this->normalizeSantehRolletsPrices($prices);

        $pricedHeights = array_keys($prices);
        sort($pricedHeights, SORT_NUMERIC);
        $minHeight = $pricedHeights[0] ?? null;
        $pricedWidths = $minHeight !== null ? array_keys($prices[$minHeight] ?? []) : [];
        sort($pricedWidths, SORT_NUMERIC);
        $minWidth = $pricedWidths[0] ?? null;
        $minPrice = ($minWidth !== null && $minHeight !== null) ? $prices[$minHeight][$minWidth] : null;

        Cache::forever(self::SANTEH_ROLLETS_CACHE_KEY, [
            'widths' => array_values($widths),
            'heights' => array_values($heights),
            'prices' => $prices,
            'min_width' => $minWidth,
            'min_height' => $minHeight,
            'min_price' => $minPrice,
        ]);

        $cachedSheets[] = self::SANTEH_ROLLETS_CACHE_KEY;
        Cache::forever('cached_sheets', array_unique($cachedSheets));
    }

    private function normalizeSantehRolletsPrices(array $prices): array
    {
        $heights = array_keys($prices);
        sort($heights, SORT_NUMERIC);

        $previousHeight = null;
        foreach ($heights as $height) {
            $widths = array_keys($prices[$height]);
            sort($widths, SORT_NUMERIC);

            $previousWidth = null;
            foreach ($widths as $width) {
                $price = (float) $prices[$height][$width];
                $floor = 0.0;

                if ($previousWidth !== null && isset($prices[$height][$previousWidth])) {
                    $floor = max($floor, (float) $prices[$height][$previousWidth]);
                }
                if ($previousHeight !== null && isset($prices[$previousHeight][$width])) {
                    $floor = max($floor, (float) $prices[$previousHeight][$width]);
                }

                if ($floor > 0 && $price < $floor) {
                    $prices[$height][$width] = $floor;
                }

                $previousWidth = $width;
            }

            $previousHeight = $height;
        }

        return $prices;
    }
}



// php artisan cache:clear
