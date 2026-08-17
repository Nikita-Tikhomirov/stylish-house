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
        if (!is_array($sheetData)) {
            return ['price' => null, 'error' => self::ERROR_SHEET_NOT_FOUND];
        }

        foreach ($sheetData as $row) {
            if (!is_array($row)) {
                return ['price' => null, 'error' => self::ERROR_SHEET_NOT_FOUND];
            }
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
            $groups = $this->woodTariffGroups($sheetData);
            if ($groups === null) {
                return ['price' => null, 'error' => self::ERROR_SHEET_NOT_FOUND];
            }

            $price = $calcPrice($groups, $prodTitle, $width, $height, 0.8);
            return $price === null
                ? ['price' => null, 'error' => self::ERROR_PRICE_NOT_FOUND]
                : ['price' => $this->finalizePrice((float) $price, $prodTitle), 'error' => null];
        }

        if (in_array($modelName, ['Дерево, бамбук 25 мм'], true)) {
            $groups = $this->woodTariffGroups($sheetData);
            if ($groups === null) {
                return ['price' => null, 'error' => self::ERROR_SHEET_NOT_FOUND];
            }

            $price = $calcPrice($groups, $prodTitle, $width, $height, 0.8);
            return $price === null
                ? ['price' => null, 'error' => self::ERROR_PRICE_NOT_FOUND]
                : ['price' => $this->finalizePrice((float) $price, $prodTitle), 'error' => null];
        }

        if (mb_strtolower(trim($modelName)) === 'горизонтальные алюминиевые') {
            $tariffs = $this->horizontalTariffMap($sheetData);
            if ($tariffs === null) {
                return ['price' => null, 'error' => self::ERROR_SHEET_NOT_FOUND];
            }

            $tariffKey = $this->horizontalProductTariffKey($prodTitle);
            if ($tariffKey === null || !array_key_exists($tariffKey, $tariffs)) {
                return ['price' => null, 'error' => self::ERROR_PRICE_NOT_FOUND];
            }

            $area = max(($width / 1000) * ($height / 1000), 1.0);

            return [
                'price' => $this->finalizePrice($tariffs[$tariffKey] * $area, $prodTitle),
                'error' => null,
            ];
        }

        if (mb_strtolower(trim($modelName)) === 'вертикальные') {
            $titleRemainder = $this->verticalProductTitleRemainder($prodTitle);
            if ($titleRemainder === '') {
                return ['price' => null, 'error' => self::ERROR_MISSING_TITLE_PART];
            }

            $billableHeight = max($height / 1000, 1.0);
            $area = max(($width / 1000) * $billableHeight, 1.0);
            $pricePerM2 = $this->findVerticalUnitPrice($sheetData, $titleRemainder);

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
        $explicitCodes = $explicitMatches[1] ?? [];
        if ($explicitCodes !== []) {
            $code = (string) end($explicitCodes);

            return $pricesByCode[$code] ?? null;
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
     * @return array<int,array{0:array<int,int>,1:float}>|null
     */
    private function woodTariffGroups(array $sheetData): ?array
    {
        $groups = [];
        $pricesByCode = [];

        foreach ($sheetData as $row) {
            $tariffLabel = null;
            foreach (['A', 'B'] as $labelColumn) {
                $label = trim((string) ($row[$labelColumn] ?? ''));
                if ($this->looksLikeWoodTariffLabel($label)) {
                    $tariffLabel = $label;
                    break;
                }
            }

            if ($tariffLabel === null) {
                continue;
            }

            $priceValue = str_replace(',', '.', trim((string) ($row['C'] ?? '')));
            if ($priceValue === '' || !is_numeric($priceValue)) {
                return null;
            }

            $codes = $this->woodCodesFromLabel($tariffLabel);
            if ($codes === []) {
                return null;
            }

            $price = (float) $priceValue;
            foreach ($codes as $code) {
                if (isset($pricesByCode[$code]) && abs($pricesByCode[$code] - $price) > 0.00001) {
                    return null;
                }

                $pricesByCode[$code] = $price;
            }

            $groups[] = [$codes, $price];
        }

        return $groups === [] ? null : $groups;
    }

    private function looksLikeWoodTariffLabel(string $label): bool
    {
        return preg_match('/(?:…|\.{3})/u', $label) === 1
            || preg_match('/^\s*\d+\s*,/u', $label) === 1;
    }

    /**
     * @return array<int,int>
     */
    private function woodCodesFromLabel(string $label): array
    {
        if (preg_match('/(?:…|\.{3})\s*(.+)$/u', $label, $listMatch)) {
            $codeList = trim($listMatch[1], " \t\n\r\0\x0B.");
        } elseif (preg_match('/^\s*\d+(?:\s*,\s*\d+)+\s*$/u', $label)) {
            $codeList = trim($label);
        } else {
            return [];
        }

        if (!preg_match('/^\d+(?:\s*,\s*\d+)+$/u', $codeList)) {
            return [];
        }

        preg_match_all('/(?<!\d)\d+(?!\d)/u', $codeList, $matches);

        return array_values(array_unique(array_map('intval', $matches[0] ?? [])));
    }

    /**
     * @return array<string,float>|null
     */
    private function horizontalTariffMap(array $sheetData): ?array
    {
        $headerFound = false;
        $currentSize = null;
        $tariffs = [];

        foreach ($sheetData as $row) {
            $widthLabel = trim((string) ($row['A'] ?? ''));
            $codesLabel = trim((string) ($row['B'] ?? ''));
            $priceValue = str_replace(',', '.', trim((string) ($row['D'] ?? '')));

            if (!$headerFound) {
                $headerFound = $this->normalizeMaterialName($widthLabel) === 'ширина ламели'
                    && $this->normalizeMaterialName($codesLabel) === 'цвет'
                    && str_contains($this->normalizeMaterialName($priceValue), 'цена');
                continue;
            }

            if (preg_match('/^(16|25)\s*мм$/ui', $widthLabel, $sizeMatch)) {
                $currentSize = (int) $sizeMatch[1];
            } elseif ($widthLabel !== '') {
                if ($codesLabel !== '' || $priceValue !== '') {
                    return null;
                }

                if ($currentSize !== null && $tariffs !== []) {
                    break;
                }

                return null;
            }

            if ($currentSize === null) {
                if ($codesLabel === '' && $priceValue === '') {
                    continue;
                }

                return null;
            }

            if ($codesLabel === '' && $priceValue === '') {
                continue;
            }

            if ($codesLabel === '' || $priceValue === '') {
                return null;
            }

            if (!is_numeric($priceValue)) {
                return null;
            }

            $parsedCodes = $this->horizontalCodesFromLabel($codesLabel);
            if ($parsedCodes === null) {
                return null;
            }

            foreach ($parsedCodes['codes'] as $code) {
                $key = $this->horizontalTariffKey($currentSize, $parsedCodes['variant'], $code);
                $price = (float) $priceValue;
                if (isset($tariffs[$key]) && abs($tariffs[$key] - $price) > 0.00001) {
                    return null;
                }

                $tariffs[$key] = $price;
            }
        }

        return $headerFound && $tariffs !== [] ? $tariffs : null;
    }

    /**
     * @return array{variant:string,codes:array<int,string>}|null
     */
    private function horizontalCodesFromLabel(string $label): ?array
    {
        $normalized = $this->normalizeMaterialName($label);
        $variant = 'standard';
        if (preg_match('/^перфорированн\p{L}*\s+/u', $normalized)) {
            $variant = 'perforated';
            $normalized = preg_replace('/^перфорированн\p{L}*\s+/u', '', $normalized) ?? $normalized;
        }

        $tokens = preg_split('/\s*,\s*/u', $normalized) ?: [];
        $codes = [];
        foreach ($tokens as $token) {
            $token = trim($token);
            if (!preg_match('/^\d+(?:[\/_]\d+)?$/', $token)) {
                return null;
            }

            $codes[] = str_replace('/', '_', $token);
        }

        $codes = array_values(array_unique($codes));

        return $codes === [] ? null : ['variant' => $variant, 'codes' => $codes];
    }

    private function horizontalProductTariffKey(string $productTitle): ?string
    {
        $normalized = $this->normalizeMaterialName($productTitle);
        if (!preg_match('/(?<!\d)(16|25)\s*мм\b/u', $normalized, $sizeMatch)) {
            return null;
        }
        if (!preg_match('/(?<!\d)(?:16|25)[-‐‑–—]\s*(\d+(?:[\/_]\d+)?)(?=\s|$)/u', $normalized, $codeMatch)) {
            return null;
        }

        $variant = str_contains($normalized, 'перф') ? 'perforated' : 'standard';
        $code = str_replace('/', '_', $codeMatch[1]);

        return $this->horizontalTariffKey((int) $sizeMatch[1], $variant, $code);
    }

    private function horizontalTariffKey(int $size, string $variant, string $code): string
    {
        return $size . '|' . $variant . '|' . $code;
    }

    private function verticalProductTitleRemainder(string $productTitle): string
    {
        $remainder = $this->normalizeMaterialName($productTitle);
        $remainder = preg_replace(
            '/^(?:тканевые|пластиковые|алюминиевые)(?:\s+|$)/u',
            '',
            $remainder
        ) ?? $remainder;

        if ($remainder === 'металлик') {
            return 'металлик глянец';
        }

        return preg_replace(
            '/^металлик\s+перфорация(?=\s|$)/u',
            'металлик перфорированный',
            $remainder
        ) ?? $remainder;
    }

    private function findVerticalUnitPrice(array $sheetData, string $titleRemainder): ?float
    {
        $candidates = [];

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

                $normalizedLabel = $this->normalizeMaterialName((string) $cellValue);
                $normalizedLabel = preg_replace('/\s*\([^)]*\)\s*$/u', '', $normalizedLabel) ?? $normalizedLabel;
                if ($normalizedLabel === '') {
                    continue;
                }

                if (preg_match('/^' . preg_quote($normalizedLabel, '/') . '(?=\s|$)/u', $titleRemainder)) {
                    $candidates[] = [
                        'length' => mb_strlen($normalizedLabel),
                        'price' => (float) $priceValue,
                    ];
                }
            }
        }

        if ($candidates === []) {
            return null;
        }

        $longestLength = max(array_column($candidates, 'length'));
        $longestPrices = array_column(array_filter(
            $candidates,
            static fn (array $candidate) => $candidate['length'] === $longestLength
        ), 'price');
        $longestPrices = array_values(array_unique($longestPrices, SORT_REGULAR));

        return count($longestPrices) === 1 ? $longestPrices[0] : null;
    }

    private function normalizeMaterialName(string $name): string
    {
        $normalized = mb_strtolower(trim($name));
        $normalized = str_replace('ё', 'е', $normalized);
        $normalized = preg_replace('/\s*%\s*/u', '% ', $normalized) ?? $normalized;
        $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;
        $normalized = trim($normalized);
        $normalized = preg_replace('/(?<!\S)трафик(?!\S)/u', 'траффик', $normalized) ?? $normalized;

        return $normalized;
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
