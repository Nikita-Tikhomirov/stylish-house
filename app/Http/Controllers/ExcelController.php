<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use App\Providers\AppServiceProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ExcelController extends Controller
{
    public function test()
    {
        $sheetData = Cache::get('sheet_' . 'Римские шторы Компакт и XL');
        // $sheetData = Cache::get('sheet_' . 'Рулонные КВАТРО Классик');
        // dd($sheetData);
        $sheetKeys = Cache::get('cached_sheets', []);
        dd($sheetKeys);

        $foundCell = null;
        $startCol = null;
        $startRow = null;
        $startRowToHeights = null;
        $startCollToHeights = null;
        $matherial = '1 категория'; // Измените на нужную категорию, например '2 категория'
        $testArr = [];

        // Делаем регулярку динамичной, используя переменную $matherial
        $materialPattern = preg_quote($matherial, '/'); // Экранируем спецсимволы в строке
        $materialPattern = "/^{$materialPattern}$/ui"; // Строим регулярку для точного совпадения

        // Поиск ячейки с указанным материалом
        foreach ($sheetData as $rowIndex => $row) {
            foreach ($row as $colIndex => $cellValue) {
                // Удаляем пробелы и специальные символы в начале и конце строки
                $cleanedCellValue = trim(preg_replace('/\s+/', ' ', $cellValue));

                // Используем динамическую регулярку для поиска указанной категории
                if (preg_match($materialPattern, $cleanedCellValue)) {
                    $startCol = chr(ord($colIndex) + 2); // Старт столбца для ширины
                    $startRow = $rowIndex + 1; // Старт строки для ширины
                    $startRowToHeights = $rowIndex + 2;
                    $startCollToHeights = chr(ord($colIndex) + 1);
                    break 2;
                }
            }
        }

        // Проверка на случай, если не найдена категория
        if ($startCol === null || $startRow === null) {
            return response()->json(['error' => 'Material category not found']);
        }

        // Получение ширины и высоты из запроса
        $prodWidth = 0.6; // Конвертация из мм в метры и округление до одной десятой
        $prodHeigth = 0.5; // Аналогично для высоты

        // Извлечение данных для ширины
        $rowValues = [];
        $startColNumeric = ord($startCol) - ord('A'); // Преобразуем в индекс

        // Получаем последний столбец, но учитываем, что это буквенный индекс
        $highestColumnIndex = count($sheetData[$startRow]) - 1; // Проверяем до конца строки

        // Проверим, что последний столбец существует
        if ($highestColumnIndex < $startColNumeric) {
            dd("Invalid column range. StartCol: {$startColNumeric}, HighestColumn: {$highestColumnIndex}");
        }

        for ($col = $startColNumeric; $col <= $highestColumnIndex; $col++) {
            $colLetter = chr($col + ord('A')); // Преобразуем обратно в букву
            $rowValues[$colLetter] = isset($sheetData[$startRow][$colLetter])
                ? trim($sheetData[$startRow][$colLetter])
                : null;
        }

        // Извлечение данных для высоты
        $heightsArr = [];
        $row = $startRowToHeights;
        while (isset($sheetData[$row][$startCollToHeights]) && trim($sheetData[$row][$startCollToHeights]) !== '') {
            $heightsArr[$row] = trim($sheetData[$row][$startCollToHeights]);
            $row++;

            // Проверим, если выход за пределы данных
            if ($row > count($sheetData)) {
                dd("Exceeded row limit. Current row: {$row}, Total rows: " . count($sheetData));
            }
        }

        // Поиск цены по ширине и высоте
        foreach ($rowValues as $widthIndex => $widthValue) {
            if ($widthValue == $prodWidth) {
                foreach ($heightsArr as $heightIndex => $heightValue) {
                    if ($heightValue == $prodHeigth) {
                        $price = isset($sheetData[$heightIndex][$widthIndex])
                            ? trim($sheetData[$heightIndex][$widthIndex])
                            : null;

                        // Логируем найденную цену
                        dd(['price' => $price]);
                    }
                }
            }
        }

        return response()->json(['error' => 'No matching price found']);
    }




    public function getProdPrice(Request $request)
    {
        // \Log::info('=== getProdPrice START ===', $request->all());

        $modelName = $request->input('model');
        $sheetData = Cache::get('sheet_' . trim($modelName));
        $material = $request->input('cloth');
        $control = $request->input('control');
        $modelId = (int) $request->input('modelId');


        $materialPattern = "/^" . preg_quote($material, '/') . "$/ui";

        if ($sheetData === null) {
            // \Log::warning('Sheet not found', ['model' => $modelName]);
            return response()->json(['error' => 'Sheet not found'], 404);
        }

        $startCol = null;
        $startRow = null;
        $startRowToHeights = null;
        $startCollToHeights = null;

        // Римские шторы
        if (in_array($modelName, ['Римские шторы Компакт и XL'])) {
            if ($modelId === 79) {
                $startCol = 'C';
                $startRow = 12;
                $startCollToHeights = 'B';
                $startRowToHeights = 54;
            } else {
                $startCol = 'C';
                $startRow = 12;
                $startCollToHeights = 'B';
                $startRowToHeights = 13;
            }

        } else {
            // Поиск материала
            foreach ($sheetData as $rowIndex => $row) {
                foreach ($row as $colIndex => $cellValue) {
                    $cleanedCellValue = trim(preg_replace('/\s+/', ' ', $cellValue));
                    if (preg_match($materialPattern, $cleanedCellValue)) {
                        if (in_array($modelName, ['Комбо Уни-1 (белый)', 'Комбо УНИ-2 (белый)', 'Комбо УНИ-2 (лам)'])) {
                            $startCol = chr(ord($colIndex) + 4);
                            $startCollToHeights = chr(ord($colIndex) + 3);
                        } else {
                            $startCol = chr(ord($colIndex) + 2);
                            $startCollToHeights = chr(ord($colIndex) + 1);
                        }
                        $startRow = $rowIndex + 1;
                        $startRowToHeights = $rowIndex + 2;

                        break 2;
                    }
                }
            }
        }

        // Функция расчета цены по группе
        $calcPrice = function ($groups, $prodTitle, $width, $height) {
            foreach ($groups as [$numbers, $unitPrice]) {
                foreach ($numbers as $number) {
                    if (preg_match('/' . preg_quote($number, '/') . '/u', $prodTitle)) {
                        return ($width / 1000) * ($height / 1000) * $unitPrice;
                    }
                }
            }
            return null;
        };

        $prodTitle = str_replace('Заказать ', '', $request->input('prodTitle'));
        $width = $request->input('width');
        $height = $request->input('height');
        $price = null;

        // Дерево 50 мм
        if (in_array($modelName, ['Дерево, бамбук 50 мм АБСОЛЮТ'])) {
            if ($modelId === 71) {
                $groups = [
                    [range(301, 305), 13743.4],
                    [range(306, 310), 14378.2],
                    [range(201, 206), 12519.4],
                ];
            } else {
                $groups = [
                    [range(31, 44), 12690.5],
                    [range(51, 63), 12690.5],
                    [[10, 13, 15, 16, 20, 22, 23, 24], 11241.5],
                ];
            }
            $price = $calcPrice($groups, $prodTitle, $width, $height);
            // \Log::info('Цена дерево 50 мм', compact('price'));
            return response()->json(['price' => $price !== null ? round($price) : 'Цена по запросу ']);
        }

        // Дерево 25 мм
        if (in_array($modelName, ['Дерево, бамбук 25 мм'])) {
            if ($modelId === 68) {
                $groups = [
                    [range(25, 71), 10787.5],
                    [[10, 13, 15, 16, 20, 22, 23, 24], 9879.4],
                ];
            } else {
                $groups = [
                    [range(201, 206), 11242.9],
                ];
            }
            $price = $calcPrice($groups, $prodTitle, $width, $height);
            // \Log::info('Цена дерево 25 мм', compact('price'));
            return response()->json(['price' => $price !== null ? round($price) : 'Цена по запросу ']);
        }

        // Горизонтальные алюминиевые
        if (mb_strtolower(trim($modelName)) === 'горизонтальные алюминиевые') {
            $groups = [];
            if ($modelId === 66) {
                $groups = [
                    [[100], 1738.8],
                    [[10, 17, 159, 19, 23, 27, 292, 39, 40, 44, 46, 48, 50, 56, 67, 84, 97, 104, 106, 130, 146, 163, 187, 188, 189, 330, 427, 497, 532, 608, 611, 7016, 730], 1959.6],
                    [[1, 203, 207, 211, 1042], 2842.8],
                    [[772081, 772082, 772083, 772085, 772091, 772093, 772095, 772098], 3408.6],
                ];
            } elseif ($modelId === 67) {
                $groups = [
                    [[100, 130, 23, 52, 56, 7016], 2842.8],
                ];
            } elseif ($modelId === 65) {
                $groups = [
                    [[21, 23, 48, 56, 79, 90, 100, 187, 7016], 2939.4],
                    [[772082, 772085, 772091, 772093, 772095, 772098], 4650.6],
                ];
            }
            $price = $calcPrice($groups, $prodTitle, $width, $height);
            // \Log::info('Цена горизонтальные алюминиевые', compact('price'));
            return response()->json(['price' => $price !== null ? round($price) : 'Цена по запросу ']);
        }

        // Вертикальные жалюзи 
        if (mb_strtolower(trim($modelName)) === 'вертикальные') {


            // Второе слово из prodTitle
            $words = preg_split('/\s+/', trim($prodTitle));
            $searchName = $words[1] ?? null;
            if (!$searchName) {
                return response()->json(['price' => 'Цена по запросу ']);
            }
            $searchName = mb_strtolower($searchName);

            // Размеры в метрах
            $widthM = $width / 1000;
            $heightM = $height / 1000;
            $area = $widthM * $heightM;

            $pricePerM2 = null;

            // Поиск по всем ячейкам
            foreach ($sheetData as $row) {
                foreach ($row as $colIndex => $cellValue) {
                    if (mb_strtolower(trim((string) $cellValue)) === $searchName) {
                        // Берем цену из соседней колонки справа
                        $nextColIndex = chr(ord($colIndex) + 1); // Преобразуем букву колонки
                        $priceVal = $row[$nextColIndex] ?? null;

                        if ($priceVal !== null) {
                            $pricePerM2 = (float) str_replace(',', '.', $priceVal);
                            break 2; // Выходим из обоих циклов
                        }
                    }
                }
            }

            if ($pricePerM2 === null) {
                return response()->json(['price' => 'Цена по запросу ']);
            }

            $totalPrice = $pricePerM2 * $area;

            return response()->json(['price' => round($totalPrice)]);


        }

        // Общий поиск по таблице
        if ($startCol === null || $startRow === null) {
            // \Log::warning('Материалная категория не найдена', ['material' => $material]);
            return response()->json(['error' => 'Material category not found']);
        }

        $prodWidth = round($width / 1000, 1);
        $prodHeight = round($height / 1000, 1);

        // \Log::info('Общий поиск', compact('prodWidth', 'prodHeight'));

        $rowValues = [];
        $startColNumeric = ord($startCol) - ord('A');
        $highestColumnIndex = count($sheetData[$startRow]) - 1;

        for ($col = $startColNumeric; $col <= $highestColumnIndex; $col++) {
            $colLetter = chr($col + ord('A'));
            $rowValues[$colLetter] = $sheetData[$startRow][$colLetter] ?? null;
        }

        $heightsArr = [];
        $row = $startRowToHeights;
        while (isset($sheetData[$row][$startCollToHeights]) && trim($sheetData[$row][$startCollToHeights]) !== '') {
            $heightsArr[$row] = trim($sheetData[$row][$startCollToHeights]);
            $row++;
        }

        foreach ($rowValues as $widthIndex => $widthValue) {
            if ($widthValue == $prodWidth) {
                foreach ($heightsArr as $heightIndex => $heightValue) {
                    if ($heightValue == $prodHeight) {
                        $price = $sheetData[$heightIndex][$widthIndex] ?? null;

                        if ($control === 'true') {
                            $elektroPrices = [];
                            $pultPrices = [];
                            foreach ($sheetData as $rowData) {
                                foreach ($rowData as $colIndex => $cellValue) {
                                    if (strpos($cellValue, 'Электропривод') !== false) {
                                        foreach ($rowData as $priceColIndex => $priceValue) {
                                            if ($priceColIndex > $colIndex && is_numeric($priceValue)) {
                                                $elektroPrices[] = $priceValue;
                                                break;
                                            }
                                        }
                                    }
                                    if (strpos($cellValue, 'Пульт управления') !== false) {
                                        foreach ($rowData as $priceColIndex => $priceValue) {
                                            if ($priceColIndex > $colIndex && is_numeric($priceValue)) {
                                                $pultPrices[] = $priceValue;
                                                break;
                                            }
                                        }
                                    }
                                }
                            }
                            $price = ($price ?? 0) + (!empty($elektroPrices) ? min($elektroPrices) : 0) + (!empty($pultPrices) ? min($pultPrices) : 0);
                            // \Log::info('Цена с электроприводом и пультом', compact('price'));
                        }

                        return response()->json(['price' => $price !== null ? round($price) : 'Цена по запросу ']);
                    }
                }
            }
        }

        // \Log::warning('Цена по запросу ', ['prodWidth' => $prodWidth, 'prodHeight' => $prodHeight]);
        return response()->json(['error' => 'No matching price found']);
    }





    // public function getmodelNames()
    // {
    //     // Путь к файлу Excel
    //     // Путь к файлу Excel
    //     $filePath = storage_path('app/public/table1.xls'); // путь к вашему файлу

    //     // Загружаем файл
    //     $spreadsheet = IOFactory::load($filePath);

    //     // Название нужного листа
    //     $modelName = 'Рулонные СТАНДАРТ'; // Замените на реальное название листа

    //     // Устанавливаем активный лист по названию
    //     $spreadsheet->setActiveSheetIndexByName($modelName);

    //     // Получаем активный лист
    //     $sheet = $spreadsheet->getActiveSheet();

    //     // Перебираем строки и столбцы в поисках ячейки с текстом "0 категория"
    //     $foundCell = null;
    //     foreach ($sheet->getRowIterator() as $row) {
    //         $rowIndex = $row->getRowIndex();
    //         foreach ($sheet->getColumnIterator() as $column) {
    //             $columnIndex = $column->getColumnIndex();
    //             $cellValue = $sheet->getCell($columnIndex . $rowIndex)->getValue();

    //             if ($cellValue === '0 категория') {
    //                 $foundCell = $columnIndex . $rowIndex;
    //                 break 2; // Прерываем оба цикла
    //             }
    //         }
    //     }

    //     if ($foundCell) {
    //         // Получаем координаты найденной ячейки
    //         [$startColumn, $startRow] = Coordinate::coordinateFromString($foundCell);

    //         // Получаем данные из строки ниже
    //         $nextRow = $startRow + 1; // Строка сразу под найденной
    //         $dataBelow = []; // Массив для хранения данных

    //         foreach ($sheet->getColumnIterator() as $column) {
    //             $columnIndex = $column->getColumnIndex();
    //             $cellValue = $sheet->getCell($columnIndex . $nextRow)->getValue();
    //             $dataBelow[$columnIndex] = $cellValue; // Сохраняем значение ячейки в массив
    //         }

    //         // Выводим найденную ячейку и данные справа от неё
    //         $rightData = [];
    //         for ($col = Coordinate::columnIndexFromString($startColumn) + 1; $col <= Coordinate::columnIndexFromString($startColumn) + 2; $col++) {
    //             $cellCoordinate = Coordinate::stringFromColumnIndex($col) . $startRow;
    //             $cellValue = $sheet->getCell($cellCoordinate)->getValue();
    //             $rightData[] = $cellValue;
    //         }

    //         // Получаем данные из следующего столбца справа от найденной ячейки
    //         $nextColumnIndex = Coordinate::columnIndexFromString($startColumn) + 1; // Индекс следующего столбца
    //         $nextColumn = Coordinate::stringFromColumnIndex($nextColumnIndex); // Получаем букву следующего столбца
    //         $nextColumnData = []; // Массив для хранения данных из следующего столбца

    //         // Перебираем строки начиная с строки ниже найденной
    //         foreach ($sheet->getRowIterator($startRow + 1) as $row) {
    //             $rowIndex = $row->getRowIndex();
    //             $cellValue = $sheet->getCell($nextColumn . $rowIndex)->getValue();
    //             $nextColumnData[$rowIndex] = $cellValue; // Сохраняем значение ячейки в массив
    //         }

    //         dd([
    //             'Найдена ячейка' => $foundCell,
    //             'Данные справа' => $rightData,
    //             'Ширина размеры' => $dataBelow,
    //             'Высота размеры' => $nextColumnData, // Добавляем данные следующего столбца
    //         ]);
    //     } else {
    //         dd('Ячейка с надписью "0 категория" не найдена');
    //     }

    // }



}
