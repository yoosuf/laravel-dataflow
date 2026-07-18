# Performance Tuning Guide

## Core Principles

- Keep all long-running data movement queue-driven.
- Favor keyset chunking over offset pagination.
- Use format writers/readers that stream from/to disk.

## Key Config Knobs

- `dataflow.chunking.min_size`
- `dataflow.chunking.max_size`
- `dataflow.chunking.memory_limit_bytes`
- `dataflow.chunking.estimated_row_width_bytes`
- `dataflow.chunking.database_latency_ms`
- `dataflow.chunking.worker_count`
- `dataflow.exports.distributed_threshold`
- `dataflow.exports.hydrate_models`
- `dataflow.exports.memory_limit_bytes`
- `dataflow.exports.memory_check_interval`
- `dataflow.imports.memory_limit_bytes`
- `dataflow.imports.memory_check_interval`

## Practical Strategy

- Start with smaller chunks when DB latency is unstable.
- Increase worker count only after queue throughput is stable.
- Monitor failed row reports and retry only failed partitions.

## High-Volume Export Defaults

For million-row exports, prefer:

- `DATAFLOW_EXPORT_HYDRATE_MODELS=false` (default): streams base query rows as lightweight objects/arrays
- lower `DATAFLOW_DISTRIBUTED_THRESHOLD` to force chunked queue export for large workloads
- tune `dataflow.chunking.max_size` and worker count based on storage and DB throughput

Only enable `DATAFLOW_EXPORT_HYDRATE_MODELS=true` when you explicitly rely on model-level casting/mutators in export output.

## Memory Guardrails

To keep memory from becoming the bottleneck, both import and export paths enforce configurable memory budgets during row streaming.

- Exports: `DATAFLOW_EXPORT_MEMORY_LIMIT_BYTES`, `DATAFLOW_EXPORT_MEMORY_CHECK_INTERVAL`
- Imports: `DATAFLOW_IMPORT_MEMORY_LIMIT_BYTES`, `DATAFLOW_IMPORT_MEMORY_CHECK_INTERVAL`
- Global fallback: `DATAFLOW_MEMORY_LIMIT_BYTES`

Recommended starting point:

- memory limit: `268435456` (256 MB)
- check interval: `500` rows

If usage exceeds the configured budget, the operation fails fast with a clear runtime exception instead of drifting into OOM behavior.

## Benchmarking

Run the built-in large-volume export simulation:

```bash
DATAFLOW_BENCH_ROWS=1000000 DATAFLOW_BENCH_BATCH=5000 php benchmarks/export_import_benchmark.php
```

Optional overrides:

- `DATAFLOW_BENCH_ROWS`: total rows to seed and profile (default: `1000000`)
- `DATAFLOW_BENCH_BATCH`: insert batch size while seeding (default: `5000`)
- `DATAFLOW_BENCH_OUTPUT`: output CSV path (default: `benchmarks/output/export-simulation.csv`)
- `DATAFLOW_BENCH_MODE`: `sqlite`, `mysql`, `postgresql` (or `pgsql`), or `virtual` (default: `sqlite`)
- `DATAFLOW_BENCH_MAX_WRITE_ROWS`: cap output file write volume while still measuring total matched/exported rows (default: `500000`, `0` = unlimited)

When `DATAFLOW_BENCH_MODE` is `mysql` or `postgresql`, configure DB access:

- `DATAFLOW_BENCH_DB_HOST`
- `DATAFLOW_BENCH_DB_PORT`
- `DATAFLOW_BENCH_DB_DATABASE`
- `DATAFLOW_BENCH_DB_USERNAME`
- `DATAFLOW_BENCH_DB_PASSWORD`

MySQL example:

```bash
DATAFLOW_BENCH_MODE=mysql DATAFLOW_BENCH_DB_HOST=127.0.0.1 DATAFLOW_BENCH_DB_PORT=3306 DATAFLOW_BENCH_DB_DATABASE=laravel_dataflow_bench DATAFLOW_BENCH_DB_USERNAME=root DATAFLOW_BENCH_DB_PASSWORD=secret DATAFLOW_BENCH_ROWS=1000000 php benchmarks/export_import_benchmark.php
```

PostgreSQL example:

```bash
DATAFLOW_BENCH_MODE=postgresql DATAFLOW_BENCH_DB_HOST=127.0.0.1 DATAFLOW_BENCH_DB_PORT=5432 DATAFLOW_BENCH_DB_DATABASE=laravel_dataflow_bench DATAFLOW_BENCH_DB_USERNAME=postgres DATAFLOW_BENCH_DB_PASSWORD=secret DATAFLOW_BENCH_ROWS=1000000 php benchmarks/export_import_benchmark.php
```

### Billion-row style simulation

Use virtual mode to simulate ultra-large row counts without physically inserting all rows into SQLite:

```bash
DATAFLOW_BENCH_MODE=virtual DATAFLOW_BENCH_ROWS=1000000000 DATAFLOW_BENCH_MAX_WRITE_ROWS=100000 php benchmarks/export_import_benchmark.php
```

This reports full-row export matching throughput while keeping disk output bounded.

The script prints:

- rows requested/inserted/exported
- seed/export/total durations
- export rows/sec throughput
- peak memory usage

## Enterprise-Scale Join Benchmark

Run the join-heavy benchmark with indexed tables:

```bash
DATAFLOW_ENT_USERS=250000 DATAFLOW_ENT_ORDERS_PER_USER=4 DATAFLOW_ENT_ITEMS_PER_ORDER=2 php benchmarks/enterprise_join_benchmark.php
```

Filter tuning knobs:

- `DATAFLOW_ENT_WINDOW_SECONDS`: lookback window for user recency filter (default: `31536000`)
- `DATAFLOW_ENT_MIN_GROSS_CENTS`: aggregated gross threshold in HAVING clause (default: `0`)

What it simulates:

- multi-table joins (`users`, `tenants`, `orders`, `order_items`)
- high-cardinality aggregation/grouping
- indexed filter/sort paths
- CSV export of aggregated result set

Key output metrics include total seeded rows, result row count, join export throughput, memory usage, and SQL query plan details.
