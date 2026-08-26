<?php

namespace App\Data\CatalogImport;

final class SanitizedPublicText
{
    /** @param  array<int, string>  $warnings */
    public function __construct(
        public readonly string $value,
        public readonly array $warnings,
    ) {}

    public function wasModified(): bool
    {
        return $this->warnings !== [];
    }
}
