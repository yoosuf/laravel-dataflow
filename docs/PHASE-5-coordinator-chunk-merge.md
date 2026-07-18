# Phase 5: Distributed Coordinator + Adaptive Chunking + Merge

## What was delivered

- Distributed export coordinator implementing `ExportCoordinatorContract`
- Adaptive chunk size resolver (`AdaptiveChunkSizeResolver`) based on memory, row width, latency, and workers
- Chunk planning by keyset ranges (`chunkById` semantics, never OFFSET)
- Chunk execution job (`RunExportChunkJob`) + merge job (`MergeExportChunksJob`)
- Format-aware merge strategy (`FormatAwareMergeStrategy`) with stream-based CSV and line-oriented merges
- Cache-backed progress store (`CacheProgressStore`) with `ProgressSnapshot`
- Export queue flow now routes through the coordinator

## Coordinator behavior

- Computes total rows from composed query
- Chooses strategy:
  - small: dispatch single `RunExportJob`
  - large: split into key ranges, dispatch chunk chain, then merge
- Stores progress snapshots in cache under `dataflow:progress:{runId}`

## Config knobs

```php
'chunking' => [
    'min_size' => 250,
    'max_size' => 10000,
    'memory_limit_bytes' => 268435456,
    'estimated_row_width_bytes' => 1024,
    'database_latency_ms' => 40,
    'worker_count' => 4,
],

'exports' => [
    'distributed_threshold' => 50000,
    'temp_prefix' => 'exports/temp',
],
```

## Tests

- `tests/Feature/ExportCoordinatorTest.php`
- `tests/Unit/AdaptiveChunkSizeResolverTest.php`

## Trade-off

Current merge implementation is strongest for CSV/NDJSON line-streaming and serves as the phase foundation; richer format-aware merges (especially structured JSON arrays and binary formats) are expanded in later phases.
