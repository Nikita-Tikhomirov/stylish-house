<?php

namespace App\Services\CatalogImport\DatabaseBackup;

final class NativeBackupCleanupPathGuard implements BackupCleanupPathGuard
{
    public function allowsDelete(string $path, string $expectedDestination): bool
    {
        if (! $this->pathsEqual(dirname($path), $expectedDestination)
            || ! is_dir($expectedDestination)
            || is_link($expectedDestination)) {
            return false;
        }

        $currentDestination = realpath($expectedDestination);

        return is_string($currentDestination)
            && $this->pathsEqual($currentDestination, $expectedDestination)
            && $this->pathsEqual(realpath(dirname($path)) ?: '', $expectedDestination);
    }

    private function pathsEqual(string $first, string $second): bool
    {
        return $this->normalizePath($first) === $this->normalizePath($second);
    }

    private function normalizePath(string $path): string
    {
        $normalized = str_replace('\\', '/', $path);
        $isUnc = str_starts_with($normalized, '//');
        $normalized = preg_replace('#/+#', '/', $normalized) ?? $normalized;
        if ($isUnc) {
            $normalized = '/'.$normalized;
        }
        $normalized = rtrim($normalized, '/');
        if ($normalized === '') {
            $normalized = '/';
        }

        if (PHP_OS_FAMILY === 'Windows'
            || preg_match('/\A[A-Za-z]:/', $normalized) === 1
            || str_starts_with($normalized, '//')) {
            return strtolower($normalized);
        }

        return $normalized;
    }
}
