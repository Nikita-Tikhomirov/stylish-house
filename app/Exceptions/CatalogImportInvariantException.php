<?php

namespace App\Exceptions;

use InvalidArgumentException;

final class CatalogImportInvariantException extends InvalidArgumentException implements SafeCatalogImportException
{
    /** @var array<string, string> */
    private const MESSAGES = [
        'landing_slug' => 'Landing target slug must be lowercase kebab-case.',
        'landing_empty' => 'Landing label becomes empty after sanitization.',
    ];

    private function __construct(
        private readonly string $catalogImportCode,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function for(string $safeCode): self
    {
        $message = self::MESSAGES[$safeCode] ?? null;
        if ($message === null) {
            throw new InvalidArgumentException('Unknown catalog import invariant error code.');
        }

        return new self($safeCode, $message);
    }

    public function safeCode(): string
    {
        return $this->catalogImportCode;
    }
}
