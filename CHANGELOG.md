# Changelog

All notable changes to this project will be documented in this file.

The format is based on Keep a Changelog and this project adheres to Semantic Versioning.

## [Unreleased]

## [0.9.3] - 2026-07-19

### Added
- Opt-in exporter fallback support via `dataflow.exports.fallback` configuration.
- Fallback coverage tests for exporter resolution and invalid fallback configuration handling.

### Changed
- Exporter resolution now validates configured exporter classes and can route through configured fallback formats when enabled.
- README benchmark results consolidated into a single multi-profile table.
- Performance tuning docs updated with unified enterprise benchmark engine options and Docker commands for Oracle and SQL Server.

## [0.9.2] - 2026-07-18

### Added
- Enterprise benchmark engine support for SQL Server (`mssql` / `sqlsrv`) and Oracle (`oracle` / `oci`) modes.
- Docker benchmark result documentation for Oracle Free 23c and SQL Server 2022 (100k-user profile).

### Changed
- Enterprise benchmark now uses deterministic explicit primary keys across engines for consistent seed behavior.
- Oracle benchmark sessions now set NLS date/timestamp formats for portable datetime insert/filter handling.

## [0.9.1] - 2026-07-18

### Added
- README benchmark results section for enterprise 1M-user Docker runs across MySQL, PostgreSQL, and MariaDB.
- Side-by-side throughput ratio summary for multi-engine comparison.

### Changed
- Excluded generated benchmark output artifacts from version control via `.gitignore`.

### Changed
- Enforced strict Parquet export integration via `ParquetWriterContract`; removed fallback `.jsonl` artifact behavior.
- Tightened queued import semantics so progress and lifecycle events are emitted from queued job execution boundaries.

### Added
- Dependency-guarded integration tests for real XLSX and PDF exports.
- Regression test ensuring Parquet exports do not emit fallback artifacts.
- Queued import lifecycle tests covering pending/completed/failed transitions and event timing.
- Sync-mode support for complex builder-based query sources via `DataFlow::forQuery(...)`.
- Queued support for complex builder-based query sources via serialized `QuerySpecification` reconstruction inside jobs.
- Query specification coverage tests for reconstruction parity and distributed queue chaining.
- Million-row export simulation benchmark with throughput and memory reporting.
- Ultra-scale virtual export simulation mode for billion-row style workloads with bounded output writes.

### Notes
- Query specifications are reconstructed by primary-key subquery scoping to preserve complex source constraints across queue boundaries.

## [0.1.0] - 2026-07-18

### Added
- Initial Phase 0 package skeleton
- Laravel service provider with publishable config
- Baseline quality tooling and CI scaffold
- ADR-001 and phase implementation plan

## [0.2.0] - 2026-07-18

### Added
- Phase 1 public API surface with contracts for fluent builder and extension points
- Readonly DTO/value object set for filters, search, sorting, import/export planning, chunk sizing, and progress snapshots
- Enum set for operators, formats, run status, logical combinators, and soft-delete modes
- ADR-002 documenting contract boundaries and KISS guardrails
- Unit test coverage for enums, DTO invariants, and interface surface

## [0.3.0] - 2026-07-18

### Added
- Phase 2 filtering engine with strict allowlist enforcement
- Nested boolean filter groups and relation/relation-count filter support
- JSON path expression factory with dialect-aware SQL generation seam
- Query composer pipeline with soft-delete mode and filter pipes
- Feature tests for allowlist security and composed query behavior
- Phase 2 usage documentation

## [0.4.0] - 2026-07-18

### Added
- Phase 3 search engine with pluggable database LIKE strategy
- Multi-term, weighted column, and relation-aware search support
- Phase 3 sorting engine with strict allowlist support
- Relation subquery sorting strategy and custom sort strategy hooks
- Query composer integration pipes for search and sorting
- Feature tests for search and sorting behavior and safety constraints

## [0.5.0] - 2026-07-18

### Added
- Phase 4 streaming export runtime for CSV, JSON, and NDJSON formats
- Exporter registry/factory with configurable format mappings
- Fluent builder integration through `DataFlow::for(...)`
- Export operation with synchronous and queued execution paths
- Queue job `RunExportJob` for async export processing
- Feature tests for sync exports and queue dispatch

## [0.6.0] - 2026-07-18

### Added
- Phase 5 distributed export coordinator
- Adaptive chunk resolver driven by memory/latency/worker inputs
- Keyset chunk planning and chunk export jobs
- Streaming merge strategy and merge job
- Cache-backed progress snapshot storage
- Coordinator and chunking test coverage

## [0.7.0] - 2026-07-18

### Added
- Phase 6 export formats: XLSX, PDF, XML, Parquet adapter
- Phase 6 import engine with CSV/XLSX/JSON/NDJSON readers
- Import mapping pipeline, serializable map implementation, and preview service/API
- Import job and chunked persistence with error report output

## [0.8.0] - 2026-07-18

### Added
- Phase 7 lifecycle events for import/export operations
- Status and mapping-preview endpoints under package route prefix
- Artisan generators for filter and exporter scaffolds

## [0.9.0] - 2026-07-18

### Added
- Phase 8 hardening artifacts: benchmark scaffold, performance tuning guide
- Mutation-testing workflow and composer script
- SemVer policy and release checklist documentation
