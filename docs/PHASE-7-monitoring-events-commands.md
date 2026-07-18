# Phase 7: Monitoring + Events + Commands

## What was delivered

- Lifecycle events:
  - `ExportStarted`, `ExportCompleted`, `ExportFailed`
  - `ImportStarted`, `ImportCompleted`, `ImportFailed`
- Status API endpoint:
  - `GET /dataflow/status/{runId}`
- Mapping preview endpoint:
  - `POST /dataflow/mapping-preview`
- Artisan generators:
  - `dataflow:filter`
  - `dataflow:exporter`

## Tests

- `tests/Feature/StatusApiTest.php`
- `tests/Feature/MappingPreviewApiTest.php`

## Notes

- Routes are loaded only when `dataflow.monitoring.enabled=true`.
- Progress snapshots are backed by cache through `ProgressStoreContract`.
- Queued imports now follow strict lifecycle semantics:
  - `queue()` seeds a `pending` snapshot only.
  - `ImportStarted` is emitted when the queued job starts execution.
  - `ImportCompleted`/`ImportFailed` are emitted by the queued job after terminal status updates.
