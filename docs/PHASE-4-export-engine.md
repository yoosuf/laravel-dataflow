# Phase 4: Export Engine (CSV, JSON, NDJSON)

## What was delivered

- Streaming exporters for CSV, JSON, and NDJSON
- Exporter factory with format-to-driver mapping
- Export runner that composes query filters/search/sorts and streams rows via `cursor()`
- Fluent export API integration:
  - `DataFlow::for(Model::class)`
  - `->search(...)`, `->sort(...)`, `->fromRequest(...)`
  - `->export('csv|json|ndjson')->to(disk, path)->sync()`
  - `->export(...)->queue()`
- Queue job (`RunExportJob`) for asynchronous export execution

## Exporter configuration

```php
'exports' => [
    'queue' => env('DATAFLOW_EXPORT_QUEUE', 'default'),
    'exporters' => [
        'csv' => \Yoosuf\LaravelDataFlow\Exporting\Exporters\CsvExporter::class,
        'json' => \Yoosuf\LaravelDataFlow\Exporting\Exporters\JsonExporter::class,
        'ndjson' => \Yoosuf\LaravelDataFlow\Exporting\Exporters\NdjsonExporter::class,
    ],
],
```

## Usage example

```php
use Yoosuf\LaravelDataFlow\DataFlow;

DataFlow::for(User::class)
    ->fromRequest($request)
    ->search($request->string('q')->toString())
    ->sort($request->query('sort'))
    ->export('csv')
    ->to('s3', 'exports/users.csv')
    ->queue();
```

## Tests

Feature coverage lives in:
- `tests/Feature/ExportEngineTest.php`

Covered behaviors:
- sync CSV export
- sync JSON/NDJSON export
- queued export dispatch
