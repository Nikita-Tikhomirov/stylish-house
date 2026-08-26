<?php

namespace App\Exceptions;

interface SafeCatalogImportException
{
    public function safeCode(): string;
}
