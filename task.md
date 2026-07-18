# Laravel DataFlow Task Plan

Status legend:
- [ ] Not started
- [-] In progress
- [x] Completed

## Phase 0: Package Skeleton

- [x] Create package structure and PSR-4 autoload skeleton
- [x] Add composer manifest, Laravel auto-discovery provider, publishable config
- [x] Add quality tooling (Pint, PHPStan, Rector, Infection scaffold)
- [x] Add CI workflow matrix scaffold
- [x] Add Pest/Testbench bootstrap and smoke test
- [x] Add ADR-001 overall architecture
- [x] Add baseline README, CHANGELOG, UPGRADE

## Phase 1: Public API Surface (Contracts, DTOs, Value Objects, Enums)

- [x] Define extension contracts for filters, search, sorting, query pipes, import/export drivers
- [x] Define public fluent API contracts (factory/builder/operations)
- [x] Add readonly DTOs and value objects for plans, filters, search, sorts, chunks, progress
- [x] Add enums for operators, formats, soft-delete mode, status, sorting, logical combinators
- [x] Add ADR(s) for abstraction boundaries and KISS rationale
- [x] Add unit tests for enum values and DTO invariants
- [x] Update docs and upgrade notes for phase output

## Phase 2: Filtering Engine + Query Composer

- [x] Implement allowlist filter engine with nested groups and relationship filters
- [x] Implement JSON path operators, null modes, date range, model scope filters
- [x] Implement query composer pipeline with dialect adapter seam
- [x] Add tests for allowlist security and query behavior
- [x] Document filtering DSL and extension examples

## Phase 3: Search + Sorting Engines

- [x] Implement pluggable search driver strategy (database LIKE baseline)
- [x] Implement weighted/multi-keyword and relationship search
- [x] Implement sortable allowlist and custom sort strategy support
- [x] Add relation sorting via subquery strategy
- [x] Add integration tests and docs

## Phase 4: Export Engine (CSV, JSON, NDJSON)

- [x] Implement streaming exporter contract lifecycle: open/write/close
- [x] Implement CSV/JSON/NDJSON streaming writers
- [x] Add fluent export builder integration
- [x] Add queue and sync execution paths
- [x] Add tests and docs

## Phase 5: Coordinator + Adaptive Chunk + Merge

- [x] Implement distributed export coordinator state flow
- [x] Implement adaptive chunk resolver
- [x] Implement streaming merge strategy
- [x] Implement retry/resume from failed chunks only
- [x] Add progress tracking tests and docs

## Phase 6: Remaining Formats + Import Engine

- [x] Add XLSX/PDF/XML/Parquet exporters
- [x] Implement import readers (CSV/XLSX/JSON/NDJSON)
- [x] Implement import mapping (headers/index, transforms, defaults, required/optional)
- [x] Implement mapping preview API and serializable maps
- [x] Implement chunked insert/upsert with row error report output
- [x] Add tests and docs

## Phase 7: Monitoring + Events + Commands

- [x] Add lifecycle events for import/export pipelines
- [x] Add status API and optional dashboard endpoints
- [x] Add artisan generators (`dataflow:filter`, `dataflow:exporter`)
- [x] Add tests and docs

## Phase 8: Hardening + Release Readiness

- [x] Add performance benchmarks and tuning guidance
- [x] Add mutation testing workflow and threshold policy
- [x] Finalize docs and examples
- [x] Finalize release checklist, SemVer policy, upgrade notes
