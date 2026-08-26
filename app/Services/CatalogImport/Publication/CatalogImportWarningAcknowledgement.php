<?php

namespace App\Services\CatalogImport\Publication;

use App\Models\CatalogImportRun;
use JsonException;

final class CatalogImportWarningAcknowledgement
{
    public function acknowledge(CatalogImportRun $run, string $operator): void
    {
        $operator = $this->validatedOperator($operator);
        $run->update([
            'warnings_acknowledged_at' => now(),
            'warnings_acknowledged_by' => $operator,
            'warnings_acknowledged_sha256' => $this->digest($run),
        ]);
    }

    public function validatedOperator(string $operator): string
    {
        $operator = trim($operator);
        if ($operator === '' || mb_strlen($operator) > 191) {
            throw new CatalogImportPublicationException(
                'Warning acknowledgement requires an operator name of at most 191 characters.'
            );
        }

        return $operator;
    }

    public function matches(CatalogImportRun $run): bool
    {
        $expected = $run->warnings_acknowledged_sha256;

        return is_string($expected)
            && preg_match('/^[a-f0-9]{64}$/D', $expected) === 1
            && hash_equals($expected, $this->digest($run));
    }

    public function digest(CatalogImportRun $run): string
    {
        $records = [];
        foreach ($run->sources()->orderBy('id')->get(['id', 'warnings']) as $source) {
            $records[] = [
                'entity' => 'source',
                'id' => $source->id,
                'warnings' => $this->warnings($source->warnings),
            ];
        }
        foreach ($run->items()->orderBy('id')->get(['id', 'warnings']) as $item) {
            $records[] = [
                'entity' => 'item',
                'id' => $item->id,
                'warnings' => $this->warnings($item->warnings),
            ];
        }
        try {
            $json = json_encode(
                $records,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
            );
        } catch (JsonException) {
            throw new CatalogImportPublicationException('Warning set cannot be fingerprinted.');
        }

        return hash('sha256', $json);
    }

    /** @return array<int, string> */
    private function warnings(mixed $value): array
    {
        $warnings = is_array($value)
            ? array_values(array_filter($value, static fn (mixed $entry): bool => is_string($entry)))
            : [];
        $warnings = array_values(array_unique($warnings, SORT_STRING));
        sort($warnings, SORT_STRING);

        return $warnings;
    }
}
