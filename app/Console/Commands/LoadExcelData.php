<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Cache;

class LoadExcelData extends Command
{
    protected $signature = 'excel:load-data';
    protected $description = 'Load Excel data and cache it';

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

        $this->info('Excel data with calculated values loaded and cached successfully!');
    }
}



// php artisan cache:clear
