<?php

declare(strict_types=1);

return [
    'defaults' => [
        'queue' => env('DATAFLOW_QUEUE', 'default'),
        'connection' => env('DATAFLOW_DB_CONNECTION'),
        'disk' => env('DATAFLOW_DISK', env('FILESYSTEM_DISK', 'local')),
    ],

    'streaming' => [
        'enabled' => true,
        'enforce_unbounded_query_guard' => true,
    ],

    'chunking' => [
        'min_size' => 250,
        'max_size' => 10000,
        'memory_limit_bytes' => 268435456,
        'estimated_row_width_bytes' => 1024,
        'database_latency_ms' => 40,
        'worker_count' => 4,
    ],

    'filters' => [
        'allowlist' => [
            // 'status' => 'status',
            // 'profile.role' => ['type' => 'json', 'column' => 'profile', 'path' => 'role'],
            // 'posts.title' => ['type' => 'relation', 'relation' => 'posts', 'column' => 'title'],
            // 'posts_count' => ['type' => 'relation-count', 'relation' => 'posts'],
            // 'tenant' => ['type' => 'scope', 'scope' => 'tenant'],
        ],
        'soft_delete_mode' => env('DATAFLOW_SOFT_DELETE_MODE', 'without-trashed'),
    ],

    'search' => [
        'columns' => [
            // 'name',
            // 'email',
        ],
        'relations' => [
            // 'posts' => ['title'],
        ],
    ],

    'sorting' => [
        'allowlist' => [
            // 'name' => 'name',
            // 'company.name' => [
            //     'type' => 'relation-subquery',
            //     'table' => 'companies',
            //     'owner_key' => 'companies.id',
            //     'foreign_key' => 'users.company_id',
            //     'column' => 'name',
            // ],
            // 'name_length' => [
            //     'type' => 'custom',
            //     'strategy' => App\\DataFlow\\Sorts\\NameLengthSort::class,
            // ],
        ],
    ],

    'exports' => [
        'queue' => env('DATAFLOW_EXPORT_QUEUE', env('DATAFLOW_QUEUE', 'default')),
        'distributed_threshold' => (int) env('DATAFLOW_DISTRIBUTED_THRESHOLD', 50000),
        'temp_prefix' => env('DATAFLOW_TEMP_PREFIX', 'exports/temp'),
        'hydrate_models' => (bool) env('DATAFLOW_EXPORT_HYDRATE_MODELS', false),
        'memory_limit_bytes' => (int) env('DATAFLOW_EXPORT_MEMORY_LIMIT_BYTES', env('DATAFLOW_MEMORY_LIMIT_BYTES', 268435456)),
        'memory_check_interval' => (int) env('DATAFLOW_EXPORT_MEMORY_CHECK_INTERVAL', 500),
        'fallback' => [
            'enabled' => (bool) env('DATAFLOW_EXPORT_FALLBACK_ENABLED', false),
            'default_format' => env('DATAFLOW_EXPORT_FALLBACK_DEFAULT_FORMAT', 'csv'),
            'format_map' => [
                // 'xlsx' => 'csv',
                // 'pdf' => 'csv',
            ],
        ],
        'exporters' => [
            'csv' => \Yoosuf\LaravelDataFlow\Exporting\Exporters\CsvExporter::class,
            'json' => \Yoosuf\LaravelDataFlow\Exporting\Exporters\JsonExporter::class,
            'ndjson' => \Yoosuf\LaravelDataFlow\Exporting\Exporters\NdjsonExporter::class,
            'xlsx' => \Yoosuf\LaravelDataFlow\Exporting\Exporters\XlsxExporter::class,
            'pdf' => \Yoosuf\LaravelDataFlow\Exporting\Exporters\PdfExporter::class,
            'xml' => \Yoosuf\LaravelDataFlow\Exporting\Exporters\XmlExporter::class,
            'parquet' => \Yoosuf\LaravelDataFlow\Exporting\Exporters\ParquetExporter::class,
        ],
    ],

    'imports' => [
        'queue' => env('DATAFLOW_IMPORT_QUEUE', env('DATAFLOW_QUEUE', 'default')),
        'chunk_size' => (int) env('DATAFLOW_IMPORT_CHUNK_SIZE', 1000),
        'error_prefix' => env('DATAFLOW_IMPORT_ERROR_PREFIX', 'imports/errors'),
        'memory_limit_bytes' => (int) env('DATAFLOW_IMPORT_MEMORY_LIMIT_BYTES', env('DATAFLOW_MEMORY_LIMIT_BYTES', 268435456)),
        'memory_check_interval' => (int) env('DATAFLOW_IMPORT_MEMORY_CHECK_INTERVAL', 500),
        'readers' => [
            'csv' => \Yoosuf\LaravelDataFlow\Importing\Readers\CsvImportReader::class,
            'xlsx' => \Yoosuf\LaravelDataFlow\Importing\Readers\XlsxImportReader::class,
            'json' => \Yoosuf\LaravelDataFlow\Importing\Readers\JsonImportReader::class,
            'ndjson' => \Yoosuf\LaravelDataFlow\Importing\Readers\NdjsonImportReader::class,
        ],
    ],

    'monitoring' => [
        'enabled' => true,
        'route_prefix' => 'dataflow',
    ],

    'features' => [
        'filters' => false,
        'search' => false,
        'sorting' => false,
        'imports' => false,
        'exports' => false,
    ],
];
