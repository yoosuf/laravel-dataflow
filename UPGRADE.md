# Upgrade Guide

## Unreleased

No upgrade notes yet.

## 0.x to 0.1.0

Initial release scaffold (Phase 0). No runtime features are available yet.

## 0.1.0 to 0.2.0

Added public API contracts, enums, and readonly DTO/value objects (Phase 1).

Notes:
- This release defines the API surface only; engine implementations begin in later phases.
- If you implement custom integrations now, depend on contracts under `Yoosuf\\LaravelDataFlow\\Contracts` and value types under `Yoosuf\\LaravelDataFlow\\DataTransferObjects`.

## 0.2.0 to 0.3.0

Phase 2 introduces filtering and query composition runtime behavior.

Notes:
- Configure `dataflow.filters.allowlist` before executing filtered queries.
- Unknown filter keys now throw `UnsupportedFilterException`.
- Soft-delete behavior is selectable through query composition mode.

## 0.3.0 to 0.4.0

Phase 3 introduces search and sorting runtime behavior.

Notes:
- Configure `dataflow.search.columns` and `dataflow.search.relations` for search scope.
- Configure `dataflow.sorting.allowlist` for safe sort fields.
- Unknown sort keys now throw `UnsupportedSortException`.

## 0.4.0 to 0.5.0

Phase 4 introduces the first production export runtime.

Notes:
- Configure `dataflow.exports.exporters` to override or extend export drivers.
- Use `DataFlow::for(Model::class)->export('csv|json|ndjson')->to(disk, path)->sync()` for direct exports.
- Use `->queue()` to dispatch asynchronous export jobs onto `dataflow.exports.queue`.

## 0.5.0 to 0.6.0

Phase 5 introduces distributed export orchestration.

Notes:
- Queued exports are now routed through the distributed coordinator.
- Configure chunk sizing and distributed threshold via `dataflow.chunking.*` and `dataflow.exports.distributed_threshold`.
- Progress snapshots are persisted in cache by run id.

## 0.6.0 to 0.7.0

Phase 6 introduces advanced export formats and the first import runtime.

Notes:
- Configure `dataflow.imports.readers` and `dataflow.imports.chunk_size` for import pipelines.
- Use `POST /dataflow/mapping-preview` to power UI column mapping flows.
- Optional packages are required for XLSX/PDF/Parquet runtime support.

## 0.7.0 to 0.8.0

Phase 7 introduces lifecycle events and monitoring endpoints.

Notes:
- New events can be listened to for hooks: export/import started/completed/failed.
- Status endpoint: `GET /dataflow/status/{runId}`.
- New artisan generators: `dataflow:filter`, `dataflow:exporter`.

## 0.8.0 to 0.9.0

Phase 8 adds release hardening assets.

Notes:
- Added benchmark scaffold and tuning guide.
- Added mutation workflow and `composer mutation` script.
- Added SemVer policy and release checklist docs.
