# Phase Plan

This document breaks implementation into strict phases. Each phase ends with docs, tests, and upgrade notes before moving forward.

## Phase 0: Package Skeleton

- Establish package structure and metadata
- Add service provider and publishable config
- Add CI, quality tooling, Pest/Testbench bootstrap
- Add ADR-001 and baseline docs
- No functional engines implemented

## Phase 1: Public API Surface

- Define contracts for extension points only
- Add readonly DTOs and value objects
- Add enums for operators, formats, statuses
- Add ADRs for each abstraction (KISS justifications)
- Add unit tests validating contracts and value semantics

## Phase 2: Filtering + Query Composer

- Implement allowlisted filtering engine
- Implement nested condition groups and relationship filters
- Implement model scope and soft-delete modes
- Build query composer pipeline (dialect-aware abstraction scaffold)
- Add feature tests for security constraints and query output
- Document filter DSL, allowlisting, and extension examples

## Phase 3: Search + Sorting

- Implement search driver strategy (database LIKE baseline)
- Add weighted/multi-keyword and relationship search support
- Implement sortable allowlist and custom sort strategy extension
- Add relation sorting via subquery pattern
- Add integration tests across query composer interactions
- Document search/sort APIs and custom driver registration

## Phase 4: Export Engine (CSV, JSON, NDJSON)

- Implement streaming exporter contracts and writer lifecycle
- Implement CSV/JSON/NDJSON exporters with bounded memory
- Add fluent builder API for export initiation
- Add queueable export job path and storage abstraction support
- Add tests for stream correctness and large dataset memory profile checks
- Document exporter extension and usage patterns

## Phase 5: Coordinator + Adaptive Chunk + Merge

- Add distributed export coordinator state machine
- Add adaptive chunk size engine (latency/memory/row-width inputs)
- Add streaming merge engine with format-aware strategy
- Add failure recovery and resume from last successful chunk
- Add progress tracking primitives
- Add resilience and retry-focused integration tests

## Phase 6: Remaining Formats + Import Engine

- Add XLSX, PDF, XML, Parquet export strategies
- Implement import readers (CSV, XLSX, JSON, NDJSON) in stream mode
- Add import mapping API (header/index mapping, transforms, defaults)
- Add mapping preview API and serializable ImportMap contract
- Add chunked insert/upsert pipelines with per-row validation and error report output
- Add end-to-end import tests and docs

## Phase 7: Monitoring + Events + Commands

- Add lifecycle event set for import/export pipelines
- Add status/progress APIs and optional dashboard endpoints
- Add artisan generators (dataflow:filter, dataflow:exporter)
- Add operational docs and event hook examples
- Add integration tests for observability and command output

## Phase 8: Hardening + Release Readiness

- Add benchmarks and performance tuning documentation
- Add mutation-testing workflow and thresholds
- Finalize README examples, extension docs, ADR index
- Finalize changelog and upgrade notes for 1.0 release
- Add release checklist and SemVer policy documentation
