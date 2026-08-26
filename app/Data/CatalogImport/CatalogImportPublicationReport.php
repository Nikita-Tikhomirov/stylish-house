<?php

namespace App\Data\CatalogImport;

final class CatalogImportPublicationReport
{
    public function __construct(
        public readonly int $sourceCount,
        public readonly int $itemCount,
        public readonly int $membershipCount,
        public readonly int $publicAttributeMembershipCount,
        public readonly int $warningCount,
        public readonly bool $warningsAcknowledgementRequired,
        public readonly int $categoryId,
        public readonly int $baseSubcategoryId,
    ) {}
}
