<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

final class CatalogImportOperationalException extends RuntimeException implements SafeCatalogImportException
{
    /** @var array<string, string> */
    private const MESSAGES = [
        'cleanup_manual' => 'Catalog import cleanup requires manual verification; private images were preserved where possible.',
        'source_changed_after_validation' => 'Source image changed after package validation.',
        'source_open_failed' => 'Validated source image cannot be opened for copying.',
        'image_copy_failed' => 'Private image copy did not write the expected bytes.',
        'source_changed_during_copy' => 'Source image changed during the private copy.',
        'destination_conflict' => 'Private destination contains a conflicting image.',
        'destination_layout' => 'Private destination path is outside the approved layout.',
        'destination_root' => 'Private destination root is unavailable.',
        'destination_link' => 'Private destination path traverses a link or reparse ancestor.',
        'attribute_metadata_conflict' => 'Catalog attribute metadata collision.',
        'attribute_value_conflict' => 'Catalog attribute value metadata collision.',
        'digest_conflict' => 'Existing catalog import run has a changed manifest or config digest.',
        'count_conflict' => 'Existing catalog import run no longer matches validated counts.',
        'source_conflict' => 'Existing catalog import run has a conflicting immutable source.',
        'item_conflict' => 'Existing catalog import run has a conflicting immutable item.',
        'attribute_set_conflict' => 'Existing catalog import item has a conflicting attribute set.',
        'attribute_detail_conflict' => 'Existing catalog import item has conflicting attribute metadata.',
        'membership_conflict' => 'Existing catalog import run has a conflicting membership set.',
        'image_metadata_conflict' => 'Existing catalog import item has conflicting immutable image metadata.',
        'cleanup_delete_failed' => 'A private image could not be removed during rollback cleanup.',
    ];

    private function __construct(
        private readonly string $catalogImportCode,
        string $message,
        ?Throwable $previous,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public static function for(string $safeCode, ?Throwable $previous = null): self
    {
        $message = self::MESSAGES[$safeCode] ?? null;
        if ($message === null) {
            throw new RuntimeException('Unknown catalog import operational error code.');
        }

        return new self($safeCode, $message, $previous);
    }

    public function safeCode(): string
    {
        return $this->catalogImportCode;
    }
}
