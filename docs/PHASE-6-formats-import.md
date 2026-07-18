# Phase 6: Remaining Export Formats + Import Engine

## What was delivered

- Added exporter classes for:
  - XLSX
  - PDF
  - XML
  - Parquet via strict writer contract
- Added import readers for:
  - CSV
  - XLSX
  - JSON
  - NDJSON
- Added reusable, serializable import map implementation (`ImportMap`)
- Added row mapping and transform pipeline (`RowMapper`)
- Added import execution runtime (`ImportRunner`) with:
  - chunked insert/upsert support
  - per-row validation error collection
  - error report artifact output
- Added mapping preview service + API endpoint (`POST /dataflow/mapping-preview`)

## Trade-offs

- XLSX/PDF/Parquet support relies on optional runtime packages (`openspout/openspout`, `dompdf/dompdf`, `codename/parquet`).
- Parquet export is strict by design: no fallback artifacts are emitted. Production deployments must bind a concrete writer implementation to `ParquetWriterContract`.

## Tests

- `tests/Feature/ImportEngineTest.php`
- `tests/Feature/MappingPreviewApiTest.php`
