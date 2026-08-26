<?php

// The built-in permission hardener is POSIX-only. Windows fails closed until
// an ACL-aware implementation exists; use an externally verified backup there.
$configuredBinary = env('CATALOG_IMPORT_DUMP_BINARY');

return [
    'destination' => env('CATALOG_IMPORT_BACKUP_PATH', storage_path('app/catalog-backups')),

    'public_roots' => [
        public_path(),
        storage_path('app/public'),
    ],

    'dump_binary' => is_string($configuredBinary) && trim($configuredBinary) !== ''
        ? $configuredBinary
        : null,

    'timeout_seconds' => (int) env('CATALOG_IMPORT_BACKUP_TIMEOUT', 900),
];
