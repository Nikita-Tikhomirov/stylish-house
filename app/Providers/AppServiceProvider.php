<?php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Pagination\Paginator;
class AppServiceProvider extends ServiceProvider
{
    protected static $spreadsheet; // Загруженный файл

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Загружаем файл и кэшируем данные листов
        // $this->loadExcelFile();
        Paginator::useBootstrap();
    }

    // Метод для загрузки файла и его листов
    private function loadExcelFile()
    {
        if (self::$spreadsheet === null) {
            $filePath = storage_path('app/public/table1.xls');
            self::$spreadsheet = IOFactory::load($filePath);

            foreach (self::$spreadsheet->getAllSheets() as $sheet) {
                $sheetTitle = $sheet->getTitle();
                $sheetData = [];

                foreach ($sheet->getRowIterator() as $row) {
                    $rowIndex = $row->getRowIndex();
                    $sheetData[$rowIndex] = [];

                    foreach ($row->getCellIterator() as $cell) {
                        $columnIndex = $cell->getColumn();
                        $sheetData[$rowIndex][$columnIndex] = $cell->getValue();
                    }
                }
                Cache::put('sheet_' . $sheetTitle, $sheetData, 60 * 60);
            }
        }
    }


    // Метод для получения загруженного файла
    public static function getSpreadsheet()
    {
        return self::$spreadsheet;
    }

    // Метод для получения конкретного листа по имени
    public static function getSheet($sheetName)
    {
        return Cache::get('sheet_' . $sheetName); // Возвращаем данные листа или null, если не найден
    }
}
