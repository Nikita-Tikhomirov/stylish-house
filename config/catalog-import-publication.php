<?php

$publicationEnabled = env('RIMSKIE_IMPORT_PUBLICATION_ENABLED', false);

return [
    'enabled' => in_array($publicationEnabled, [true, 1, '1', 'true'], true),
    'source_definitions' => base_path('config/rimskie-import-sources.json'),
    'lock_name' => 'catalog-import:publication-mutation',
    'lock_wait_seconds' => 5,
];
