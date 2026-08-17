<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class ProductMinPriceCalculator
{
    public const ERROR_SHEET_NOT_FOUND = 'sheet_not_found';
    public const ERROR_INVALID_DIMENSIONS = 'invalid_dimensions';
    public const ERROR_MATERIAL_NOT_FOUND = 'material_not_found';
    public const ERROR_MISSING_TITLE_PART = 'missing_title_part';
    public const ERROR_PRICE_NOT_FOUND = 'price_not_found';
    public const SANTEH_ROLLETS_CACHE_KEY = 'santeh_rollets_price_matrix';
    public const ROLLETS_OPENING_CACHE_KEY = 'rollets_opening_price_matrices';

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

        if ($this->isRolletsOpeningProduct($prodTitle, $modelName)) {
            return $this->calculateRolletsOpeningPrice($modelName, $width, $height);
        }

        if ($this->isSantehRolletsProduct($prodTitle, $modelName)) {
            return $this->calculateSantehRolletsPrice($width, $height);
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
                        if (!is_string($colIndex) || !preg_match('/^[A-Z]+$/i', $colIndex)) {
                            continue;
                        }

                        $materialColumnIndex = Coordinate::columnIndexFromString($colIndex);
                        if (in_array($modelName, ['Комбо Уни-1 (белый)', 'Комбо УНИ-2 (белый)', 'Комбо УНИ-2 (лам)'], true)) {
                            $startCol = Coordinate::stringFromColumnIndex($materialColumnIndex + 4);
                            $startCollToHeights = Coordinate::stringFromColumnIndex($materialColumnIndex + 3);
                        } else {
                            $startCol = Coordinate::stringFromColumnIndex($materialColumnIndex + 2);
                            $startCollToHeights = Coordinate::stringFromColumnIndex($materialColumnIndex + 1);
                        }
                        $startRow = $rowIndex + 1;
                        $startRowToHeights = $rowIndex + 2;
                        break 2;
                    }
                }
            }
        }

        $calcPrice = function (array $groups, string $productTitle, float $w, float $h, float $minimumArea): ?float {
            $unitPrice = $this->findUnitPriceByProductCode($groups, $productTitle);
            if ($unitPrice === null) {
                return null;
            }

            $area = max(($w / 1000) * ($h / 1000), $minimumArea);

            return $area * $unitPrice;
        };

        if (in_array($modelName, ['Дерево, бамбук 50 мм АБСОЛЮТ'], true)) {
            if ($modelId === 69) {
                $groups = [
                    [range(301, 305), 13743.4],
                    [range(306, 310), 14378.2],
                    [range(201, 206), 12519.4],
                ];
            } elseif ($modelId === 71) {
                $groups = [
                    [range(31, 44), 12690.5],
                    [range(51, 63), 12690.5],
                    [[10, 13, 15, 16, 20, 22, 23, 24], 11241.5],
                ];
            } else {
                $groups = [];
            }
            $price = $calcPrice($groups, $prodTitle, $width, $height, 0.8);
            return $price === null
                ? ['price' => null, 'error' => self::ERROR_PRICE_NOT_FOUND]
                : ['price' => $this->finalizePrice((float) $price, $prodTitle), 'error' => null];
        }

        if (in_array($modelName, ['Дерево, бамбук 25 мм'], true)) {
            if ($modelId === 68) {
                $groups = [
                    [[31, 32, 33, 35, 36, 37, 38, 39, 40, 41, 42, 43, 44, 52, 53, 56, 58, 59, 62, 63, 64, 65, 66, 67, 68, 69, 70, 71], 10787.5],
                    [[10, 13, 15, 16, 20, 22, 23, 24], 9879.4],
                ];
            } else {
                $groups = [
                    [range(201, 206), 11242.9],
                ];
            }
            $price = $calcPrice($groups, $prodTitle, $width, $height, 0.8);
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

            $price = $calcPrice($groups, $prodTitle, $width, $height, 1.0);
            return $price === null
                ? ['price' => null, 'error' => self::ERROR_PRICE_NOT_FOUND]
                : ['price' => $this->finalizePrice((float) $price, $prodTitle), 'error' => null];
        }

        if (mb_strtolower(trim($modelName)) === 'вертикальные') {
            $words = preg_split('/\s+/', trim($prodTitle));
            $searchNames = array_values(array_filter(array_slice($words ?: [], 0, 2)));
            if ($searchNames === []) {
                return ['price' => null, 'error' => self::ERROR_MISSING_TITLE_PART];
            }

            $billableHeight = max($height / 1000, 1.0);
            $area = max(($width / 1000) * $billableHeight, 1.0);
            $pricePerM2 = $this->findVerticalUnitPrice($sheetData, $searchNames);

            if ($pricePerM2 === null) {
                return ['price' => null, 'error' => self::ERROR_PRICE_NOT_FOUND];
            }

            return ['price' => $this->finalizePrice((float) ($pricePerM2 * $area), $prodTitle), 'error' => null];
        }

        if ($startCol === null || $startRow === null) {
            return ['price' => null, 'error' => self::ERROR_MATERIAL_NOT_FOUND];
        }

        $prodWidth = ceil($width / 100) / 10;
        $prodHeight = ceil($height / 100) / 10;

        $rowValues = [];
        $startColNumeric = Coordinate::columnIndexFromString($startCol);
        $highestColumnIndex = 0;
        foreach (array_keys($sheetData[$startRow] ?? []) as $columnName) {
            if (is_string($columnName) && preg_match('/^[A-Z]+$/i', $columnName)) {
                $highestColumnIndex = max(
                    $highestColumnIndex,
                    Coordinate::columnIndexFromString($columnName)
                );
            }
        }

        for ($col = $startColNumeric; $col <= $highestColumnIndex; $col++) {
            $colLetter = Coordinate::stringFromColumnIndex($col);
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
        return (int) round($basePrice) * $multiplier;
    }

    /**
     * @param array<int,array{0:array<int,int>,1:float|int}> $groups
     */
    private function findUnitPriceByProductCode(array $groups, string $productTitle): ?float
    {
        $pricesByCode = [];
        foreach ($groups as [$numbers, $unitPrice]) {
            foreach ($numbers as $number) {
                $pricesByCode[(string) $number] = (float) $unitPrice;
            }
        }

        preg_match_all('/[-‐‑–—]\s*(\d+)(?!\d)/u', $productTitle, $explicitMatches);
        $explicitCodes = array_reverse($explicitMatches[1] ?? []);
        foreach ($explicitCodes as $code) {
            if (array_key_exists($code, $pricesByCode)) {
                return $pricesByCode[$code];
            }
        }

        preg_match_all('/(?<!\d)(\d+)(?!\d)/u', $productTitle, $numericMatches, PREG_OFFSET_CAPTURE);
        $numericTokens = array_reverse($numericMatches[1] ?? []);
        foreach ($numericTokens as [$code, $offset]) {
            $suffix = substr($productTitle, $offset + strlen($code));
            if (preg_match('/^\s*мм\b/ui', $suffix)) {
                continue;
            }
            if (array_key_exists($code, $pricesByCode)) {
                return $pricesByCode[$code];
            }
        }

        return null;
    }

    /**
     * @param array<int, string> $searchNames
     */
    private function findVerticalUnitPrice(array $sheetData, array $searchNames): ?float
    {
        $normalizedSearchNames = array_values(array_unique(array_map(
            fn (string $searchName) => $this->normalizeMaterialName($searchName),
            $searchNames
        )));
        $exactCandidates = [];
        $prefixCandidates = [];

        foreach ($sheetData as $row) {
            foreach ($row as $colIndex => $cellValue) {
                if (!is_string($colIndex) || !preg_match('/^[A-Z]+$/i', $colIndex)) {
                    continue;
                }

                $nextColIndex = Coordinate::stringFromColumnIndex(
                    Coordinate::columnIndexFromString($colIndex) + 1
                );
                $priceValue = str_replace(',', '.', trim((string) ($row[$nextColIndex] ?? '')));
                if ($priceValue === '' || !is_numeric($priceValue)) {
                    continue;
                }

                $normalizedCellValue = $this->normalizeMaterialName((string) $cellValue);
                foreach ($normalizedSearchNames as $normalizedSearchName) {
                    if ($normalizedCellValue === $normalizedSearchName) {
                        $exactCandidates[] = (float) $priceValue;
                        continue 2;
                    }

                    if (preg_match('/^' . preg_quote($normalizedSearchName, '/') . '(?:[\s(\/\-]|$)/u', $normalizedCellValue)) {
                        $prefixCandidates[] = (float) $priceValue;
                        continue 2;
                    }
                }
            }
        }

        $exactCandidates = array_values(array_unique($exactCandidates, SORT_REGULAR));
        $prefixCandidates = array_values(array_unique($prefixCandidates, SORT_REGULAR));
        $candidates = array_values(array_unique(
            array_merge($exactCandidates, $prefixCandidates),
            SORT_REGULAR
        ));

        return count($candidates) === 1 ? $candidates[0] : null;
    }

    private function normalizeMaterialName(string $name): string
    {
        $normalized = mb_strtolower(trim($name));
        $normalized = str_replace('ё', 'е', $normalized);
        $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;

        return $normalized === 'трафик' ? 'траффик' : $normalized;
    }

    private function isSantehRolletsProduct(string $prodTitle, string $modelName): bool
    {
        $haystack = mb_strtolower(trim($prodTitle . ' ' . $modelName));

        return str_contains($haystack, 'сантехнические роллеты')
            || str_contains($haystack, 'сантехнические рольставни');
    }

    /**
     * Uses the сантехнические роллеты price matrix loaded by excel:load-data.
     * Requested dimensions are priced by the nearest available larger table size.
     *
     * @return array{price:int|null,error:string|null}
     */
    private function calculateSantehRolletsPrice(float $width, float $height): array
    {
        $matrix = Cache::get(self::SANTEH_ROLLETS_CACHE_KEY);
        if (!is_array($matrix)) {
            return ['price' => null, 'error' => self::ERROR_SHEET_NOT_FOUND];
        }

        $priceWidth = $this->nearestGreaterOrEqual((array) ($matrix['widths'] ?? []), $width);
        $priceHeight = $this->nearestGreaterOrEqual((array) ($matrix['heights'] ?? []), $height);

        if ($priceWidth === null || $priceHeight === null) {
            return ['price' => null, 'error' => self::ERROR_PRICE_NOT_FOUND];
        }

        $price = $this->findAvailableMatrixPrice($matrix, $priceWidth, $priceHeight);
        if ($price === null || !is_numeric($price)) {
            return ['price' => null, 'error' => self::ERROR_PRICE_NOT_FOUND];
        }

        return ['price' => (int) round((float) $price), 'error' => null];
    }

    private function isRolletsOpeningProduct(string $prodTitle, string $modelName): bool
    {
        $matrices = Cache::get(self::ROLLETS_OPENING_CACHE_KEY);
        if (!is_array($matrices) || !isset($matrices[$modelName])) {
            return false;
        }

        $haystack = mb_strtolower(trim($prodTitle));

        return str_contains($haystack, 'роллеты для проема')
            || str_contains($haystack, 'роллеты для проёма')
            || str_contains($haystack, 'роллеты на окна')
            || str_contains($haystack, 'роллеты для ворот')
            || str_contains($haystack, 'рольставни для проема')
            || str_contains($haystack, 'рольставни для проёма')
            || str_contains($haystack, 'рольставни на окна')
            || str_contains($haystack, 'рольставни для ворот')
            || str_contains($haystack, 'рольворота')
            || str_contains($haystack, 'секционные ворота')
            || str_contains($haystack, 'секционных ворот')
            || str_contains($haystack, 'промышленные ворота');
    }

    /**
     * Uses the роллеты для проема price matrix loaded by excel:load-data.
     * Requested dimensions are priced by the nearest available larger table size.
     *
     * @return array{price:int|null,error:string|null}
     */
    private function calculateRolletsOpeningPrice(string $modelName, float $width, float $height): array
    {
        $matrices = Cache::get(self::ROLLETS_OPENING_CACHE_KEY);
        if (!is_array($matrices) || !isset($matrices[$modelName]) || !is_array($matrices[$modelName])) {
            return ['price' => null, 'error' => self::ERROR_SHEET_NOT_FOUND];
        }

        $matrix = $matrices[$modelName];
        $priceWidth = $this->nearestGreaterOrEqual((array) ($matrix['widths'] ?? []), $width);
        $priceHeight = $this->nearestGreaterOrEqual((array) ($matrix['heights'] ?? []), $height);

        if ($priceWidth === null || $priceHeight === null) {
            return ['price' => null, 'error' => self::ERROR_PRICE_NOT_FOUND];
        }

        $price = $this->findAvailableMatrixPrice($matrix, $priceWidth, $priceHeight);
        if ($price === null || !is_numeric($price)) {
            return ['price' => null, 'error' => self::ERROR_PRICE_NOT_FOUND];
        }

        return ['price' => (int) round((float) $price), 'error' => null];
    }

    private function findAvailableMatrixPrice(array $matrix, int $startWidth, int $startHeight): mixed
    {
        $widths = array_values(array_filter(
            array_map(static fn ($value) => is_numeric($value) ? (int) $value : null, (array) ($matrix['widths'] ?? [])),
            static fn ($value) => $value !== null && $value >= $startWidth
        ));
        $heights = array_values(array_filter(
            array_map(static fn ($value) => is_numeric($value) ? (int) $value : null, (array) ($matrix['heights'] ?? [])),
            static fn ($value) => $value !== null && $value >= $startHeight
        ));

        sort($widths, SORT_NUMERIC);
        sort($heights, SORT_NUMERIC);

        foreach ($heights as $height) {
            foreach ($widths as $width) {
                $price = $matrix['prices'][$height][$width] ?? null;
                if ($price !== null && is_numeric($price)) {
                    return $price;
                }
            }
        }

        return null;
    }

    private function nearestGreaterOrEqual(array $values, float $needle): ?int
    {
        $numericValues = array_values(array_filter(
            array_map(static fn ($value) => is_numeric($value) ? (int) $value : null, $values),
            static fn ($value) => $value !== null
        ));

        sort($numericValues, SORT_NUMERIC);

        foreach ($numericValues as $value) {
            if ($value >= $needle) {
                return $value;
            }
        }

        return null;
    }

    private function hasDoubleKeyword(string $prodTitle): bool
    {
        return mb_stripos($prodTitle, 'дабл') !== false;
    }
}
