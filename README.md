# yoosuf/laravel-dataflow

Build production-grade data pipelines in Laravel without custom ETL glue.

`laravel-dataflow` is a streaming-first package for filtering, searching, sorting, importing, and exporting large datasets with queue-ready execution and predictable memory use.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/yoosuf/laravel-dataflow.svg?style=flat-square)](https://packagist.org/packages/yoosuf/laravel-dataflow)
[![Total Downloads](https://img.shields.io/packagist/dt/yoosuf/laravel-dataflow.svg?style=flat-square)](https://packagist.org/packages/yoosuf/laravel-dataflow)
[![Tests](https://img.shields.io/github/actions/workflow/status/yoosuf/laravel-dataflow/ci.yml?branch=main&label=tests&style=flat-square)](https://github.com/yoosuf/laravel-dataflow/actions)
[![License](https://img.shields.io/packagist/l/yoosuf/laravel-dataflow.svg?style=flat-square)](https://packagist.org/packages/yoosuf/laravel-dataflow)

## Why Laravel Teams Use It

- Ship CSV/XLSX/JSON/NDJSON/PDF/Parquet flows from one fluent API.
- Keep memory stable with stream-based processing and chunk coordination.
- Run sync for fast tasks, queue for heavy jobs, with progress snapshots.
- Keep query safety with allowlisted filters, search, and sorting.
- Integrate with existing Eloquent builders and complex query constraints.

## Quick Pitch

If your app needs admin exports, BI feeds, audit extracts, or bulk imports, this package gives you a single Laravel-native pipeline instead of ad-hoc jobs and one-off scripts.

## 30-Second Demo

Add a short terminal-to-result GIF here showing:

1. `DataFlow::for(User::class)->filter(...)->search(...)->export('csv')->to(...)->queue()`
2. Queue worker processing chunks
3. Downloadable output file

Suggested asset path: `docs/assets/demo-export.gif`

## Copy-Paste Recipes

### 1) Admin Panel Export (Queued CSV)

```php
use Yoosuf\LaravelDataFlow\DataFlow;
use App\Models\User;

$runId = DataFlow::for(User::class)
  ->allowedFilters(['status', 'country'])
  ->allowedSearch(['name', 'email'])
  ->allowedSorts(['created_at'])
  ->filter(['status' => 'active'])
  ->search('gmail.com')
  ->sort('-created_at')
  ->export('csv')
  ->to('exports', 'active-users.csv')
  ->queue();
```

### 2) BI Feed (Nightly NDJSON)

```php
use Yoosuf\LaravelDataFlow\DataFlow;
use App\Models\Order;

DataFlow::forQuery(
  Order::query()->whereDate('created_at', now()->subDay()->toDateString())
)
  ->export('ndjson')
  ->to('feeds', 'orders-nightly.ndjson')
  ->sync();
```

### 3) Bulk Import (Chunked)

```php
use Yoosuf\LaravelDataFlow\DataFlow;
use App\Models\Product;

DataFlow::for(Product::class)
  ->import('csv')
  ->from('imports', 'products.csv')
  ->map([
    'sku' => 'sku',
    'name' => 'name',
    'price' => 'price_cents',
  ])
  ->upsertBy(['sku'])
  ->queue();
```

## Copy For GitHub Repo Settings

Use this as your repository description:

> Streaming-first Laravel package for filtering, search, sorting, import, and export at any scale, with queue-native execution and low memory usage.

Use these GitHub topics:

`laravel`, `laravel-package`, `eloquent`, `data-pipeline`, `dataflow`, `import`, `export`, `csv`, `xlsx`, `ndjson`, `parquet`, `etl`, `query-builder`, `queue`, `large-datasets`

## Installation (Path Repository)

Add to your root composer repositories:

```json
{
  "type": "path",
  "url": "packages/yoosuf/laravel-dataflow",
  "options": { "symlink": true }
}
```

Then require:

```bash
composer require yoosuf/laravel-dataflow:*
```

Publish config:

```bash
php artisan vendor:publish --tag=dataflow-config
```

## Configuration

Default configuration is in `config/dataflow.php` after publishing.

### Exporter Fallback Support (Opt-In)

For production resilience, exporter resolution supports optional fallback formats when a requested exporter is not registered or unavailable.

- `dataflow.exports.fallback.enabled` (default: `false`)
- `dataflow.exports.fallback.default_format` (default: `csv`)
- `dataflow.exports.fallback.format_map` (per-format overrides, e.g. `xlsx => csv`)

Example:

```php
'exports' => [
  'fallback' => [
    'enabled' => true,
    'default_format' => 'csv',
    'format_map' => [
      'xlsx' => 'csv',
      'pdf' => 'csv',
    ],
  ],
],
```

When disabled, the package remains strict and throws an exception for unsupported formats.

## Development

```bash
composer install
composer lint
composer analyse
composer test
```

## Benchmark Results (Docker)

All enterprise join export benchmark results are consolidated below.

Common shape per profile:

- orders per user: `6`
- items per order: `3`
- workload: `users -> orders -> order_items` join + aggregate export

| Profile | Engine | Users | Orders | Order Items | Result Rows | Batch Size | Schema (s) | Seed (s) | Export (s) | Total (s) | Rows/s | Peak Mem (MB) |
|---|---|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|
| 1M | MySQL 8.4 | 1,000,000 | 6,000,000 | 18,000,000 | 517,926 | 50,000 | 0.5578 | 643.9637 | 70.5498 | 715.0712 | 7,341.29 | 45.91 |
| 1M | PostgreSQL 16 | 1,000,000 | 6,000,000 | 18,000,000 | 517,926 | 50,000 | 0.0272 | 1,039.8392 | 24.6754 | 1,064.5419 | 20,989.56 | 2.00 |
| 1M | MariaDB 11 | 1,000,000 | 6,000,000 | 18,000,000 | 517,926 | 50,000 | 16.0734 | 1,664.5361 | 162.0778 | 1,842.6873 | 3,195.54 | 45.91 |
| 100k | Oracle Free 23c | 100,000 | 600,000 | 1,800,000 | 51,326 | 10,000 | 0.2354 | 206.1710 | 2.7220 | 209.1284 | 18,856.14 | 2.00 |
| 100k | SQL Server 2022 | 100,000 | 600,000 | 1,800,000 | 51,326 | 10,000 | 0.0748 | 335.9829 | 2.6955 | 338.7532 | 19,041.18 | 2.00 |

Throughput ratios (`rows_per_second`) by profile:

- 1M profile: PostgreSQL / MySQL `2.86x`, PostgreSQL / MariaDB `6.57x`, MySQL / MariaDB `2.30x`
- 100k profile: SQL Server / Oracle `1.01x` (Oracle / SQL Server `0.99x`)

Notes:

- Measurements are single-run Docker comparisons.
- Absolute timings vary by host resources; compare engines primarily within the same profile.
- Oracle runs used Docker Oracle Free with runtime-installed `oci8` + `pdo_oci` in the PHP benchmark container.

## Complex Query Support

Use `DataFlow::forQuery($builder)` when your export/import source is a prebuilt Eloquent query with scopes, nested conditions, relation constraints, or subqueries.

```php
use Yoosuf\LaravelDataFlow\DataFlow;
use App\Models\User;

$runId = DataFlow::forQuery(
  User::query()->where('status', 'active')->whereHas('posts')
)
  ->export('csv')
  ->to('exports', 'active-users.csv')
  ->sync();
```

Builder-based query sources are supported in `sync()` mode.
Queued `queue()` runs are supported via an internal serialized query specification that reconstructs the query in worker jobs.

## Roadmap

See `docs/PHASE_PLAN.md` for the phase-by-phase task breakdown.

## License

MIT
