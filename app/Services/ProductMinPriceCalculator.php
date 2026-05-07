<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class ProductMinPriceCalculator
{
    public const ERROR_SHEET_NOT_FOUND = 'sheet_not_found';
    public const ERROR_INVALID_DIMENSIONS = 'invalid_dimensions';
    public const ERROR_MATERIAL_NOT_FOUND = 'material_not_found';
    public const ERROR_MISSING_TITLE_PART = 'missing_title_part';
    public const ERROR_PRICE_NOT_FOUND = 'price_not_found';

    /**
     * @param array{
     * model?: string|null,
     * cloth?: string|null,
     * control?: bool|string|null,
     * modelId?: int|string|null,
     * prodTitle?: string|null,
     * width?: int|float|string|null,
     * height?: int|float|string|null
     * } $payload
     * @return array{price:int|null,error:string|null}
     */
    public function calculate(array $payload): array
    {
        $modelName = trim((string) ($payload['model'] ?? ''));
        $material = trim((string) ($payload['cloth'] ?? ''));
        $control = filter_var($payload['control'] ?? false, FILTER_VALIDATE_BOOL);
        $modelId = (int) ($payload['modelId'] ?? 0);
        $prodTitle = str_replace('Заказать ', '', (string) ($payload['prodTitle'] ?? ''));
        $width = (float) ($payload['width'] ?? 0);
        $height = (float) ($payload['height'] ?? 0);

        if ($width <= 0 || $height <= 0) {
            return ['price' => null, 'error' => self::ERROR_INVALID_DIMENSIONS];
        }

        $sheetData = Cache::get('sheet_' . $modelName);
        if ($sheetData === null) {
            return ['price' => null, 'error' => self::ERROR_SHEET_NOT_FOUND];
        }

        $materialPattern = "/^" . preg_quote($material, '/') . "$/ui";

        $startCol = null;
        $startRow = null;
        $startRowToHeights = null;
        $startCollToHeights = null;

        if (in_array($modelName, ['Римские шторы Компакт и XL'], true)) {
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
            foreach ($sheetData as $rowIndex => $row) {
                foreach ($row as $colIndex => $cellValue) {
                    $cleanedCellValue = trim(preg_replace('/\s+/', ' ', (string) $cellValue));
                    if (preg_match($materialPattern, $cleanedCellValue)) {
                        if (in_array($modelName, ['Комбо Уни-1 (белый)', 'Комбо УНИ-2 (белый)', 'Комбо УНИ-2 (лам)'], true)) {
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

        $calcPrice = function (array $groups, string $productTitle, float $w, float $h): ?float {
            foreach ($groups as [$numbers, $unitPrice]) {
                foreach ($numbers as $number) {
                    if (preg_match('/' . preg_quote((string) $number, '/') . '/u', $productTitle)) {
                        return ($w / 1000) * ($h / 1000) * $unitPrice;
                    }
                }
            }
            return null;
        };

        if (in_array($modelName, ['Дерево, бамбук 50 мм АБСОЛЮТ'], true)) {
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
            return $price === null
                ? ['price' => null, 'error' => self::ERROR_PRICE_NOT_FOUND]
                : ['price' => $this->finalizePrice((float) $price, $prodTitle), 'error' => null];
        }

        if (in_array($modelName, ['Дерево, бамбук 25 мм'], true)) {
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
            return $price === null
                ? ['price' => null, 'error' => self::ERROR_PRICE_NOT_FOUND]
                : ['price' => $this->finalizePrice((float) $price, $prodTitle), 'error' => null];
        }

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
            return $price === null
                ? ['price' => null, 'error' => self::ERROR_PRICE_NOT_FOUND]
                : ['price' => $this->finalizePrice((float) $price, $prodTitle), 'error' => null];
        }

        if (mb_strtolower(trim($modelName)) === 'вертикальные') {
            $words = preg_split('/\s+/', trim($prodTitle));
            $searchName = $words[1] ?? null;
            if (!$searchName) {
                return ['price' => null, 'error' => self::ERROR_MISSING_TITLE_PART];
            }

            $searchName = mb_strtolower($searchName);
            $area = ($width / 1000) * ($height / 1000);
            $pricePerM2 = null;

            foreach ($sheetData as $row) {
                foreach ($row as $colIndex => $cellValue) {
                    if (mb_strtolower(trim((string) $cellValue)) === $searchName) {
                        $nextColIndex = chr(ord($colIndex) + 1);
                        $priceVal = $row[$nextColIndex] ?? null;
                        if ($priceVal !== null) {
                            $pricePerM2 = (float) str_replace(',', '.', (string) $priceVal);
                            break 2;
                        }
                    }
                }
            }

            if ($pricePerM2 === null) {
                return ['price' => null, 'error' => self::ERROR_PRICE_NOT_FOUND];
            }

            return ['price' => $this->finalizePrice((float) ($pricePerM2 * $area), $prodTitle), 'error' => null];
        }

        if ($startCol === null || $startRow === null) {
            return ['price' => null, 'error' => self::ERROR_MATERIAL_NOT_FOUND];
        }

        $prodWidth = round($width / 1000, 1);
        $prodHeight = round($height / 1000, 1);

        $rowValues = [];
        $startColNumeric = ord($startCol) - ord('A');
        $highestColumnIndex = count($sheetData[$startRow] ?? []) - 1;

        for ($col = $startColNumeric; $col <= $highestColumnIndex; $col++) {
            $colLetter = chr($col + ord('A'));
            $rowValues[$colLetter] = $sheetData[$startRow][$colLetter] ?? null;
        }

        $heightsArr = [];
        $row = $startRowToHeights;
        while (isset($sheetData[$row][$startCollToHeights]) && trim((string) $sheetData[$row][$startCollToHeights]) !== '') {
            $heightsArr[$row] = trim((string) $sheetData[$row][$startCollToHeights]);
            $row++;
        }

        foreach ($rowValues as $widthIndex => $widthValue) {
            if ($widthValue == $prodWidth) {
                foreach ($heightsArr as $heightIndex => $heightValue) {
                    if ($heightValue == $prodHeight) {
                        $price = $sheetData[$heightIndex][$widthIndex] ?? null;

                        if ($control) {
                            $elektroPrices = [];
                            $pultPrices = [];
                            foreach ($sheetData as $rowData) {
                                foreach ($rowData as $colIndex => $cellValue) {
                                    $stringValue = (string) $cellValue;
                                    if (strpos($stringValue, 'Электропривод') !== false) {
                                        foreach ($rowData as $priceColIndex => $priceValue) {
                                            if ($priceColIndex > $colIndex && is_numeric($priceValue)) {
                                                $elektroPrices[] = $priceValue;
                                                break;
                                            }
                                        }
                                    }
                                    if (strpos($stringValue, 'Пульт управления') !== false) {
                                        foreach ($rowData as $priceColIndex => $priceValue) {
                                            if ($priceColIndex > $colIndex && is_numeric($priceValue)) {
                                                $pultPrices[] = $priceValue;
                                                break;
                                            }
                                        }
                                    }
                                }
                            }

                            $price = ($price ?? 0)
                                + (!empty($elektroPrices) ? min($elektroPrices) : 0)
                                + (!empty($pultPrices) ? min($pultPrices) : 0);
                        }

                        if ($price === null || !is_numeric($price)) {
                            return ['price' => null, 'error' => self::ERROR_PRICE_NOT_FOUND];
                        }

                        return ['price' => $this->finalizePrice((float) $price, $prodTitle), 'error' => null];
                    }
                }
            }
        }

        return ['price' => null, 'error' => self::ERROR_PRICE_NOT_FOUND];
    }

    private function finalizePrice(float $basePrice, string $prodTitle): int
    {
        $multiplier = $this->hasDoubleKeyword($prodTitle) ? 2 : 1;
        return (int) round($basePrice * $multiplier);
    }

    private function hasDoubleKeyword(string $prodTitle): bool
    {
        return mb_stripos($prodTitle, 'дабл') !== false;
    }
}
