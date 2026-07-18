# ADR-001: Overall Architecture

- Status: Accepted
- Date: 2026-07-18

## Context

The package must support filtering, searching, sorting, imports, and exports at very large scale with near-constant memory usage. It must remain approachable to typical Laravel developers.

## Decision

1. Queue-first, stream-first architecture:
- All heavy read/write operations are streamed.
- Jobs are chunked by keyset semantics (never OFFSET for large workloads).
- Coordinators track chunk state for retries and resumability.

2. Layered module boundaries:
- Query shaping: filtering, search, sorting, query composition.
- Data movement: import, export, merge, storage.
- Orchestration: coordinator, chunk policy, monitoring.

3. Explicit extension points through contracts where needed:
- Drivers and format handlers are contract-driven.
- Internal implementation classes are final by default.

4. Laravel-native integration:
- Service provider, publishable config, queue/filesystem abstraction usage.
- No direct broker-specific APIs in package code.

## SOLID / DRY / KISS Rationale

- SOLID: Engine concerns are separated and extension points are abstracted through contracts.
- DRY: Shared stream and query pipeline primitives are centralized.
- KISS: Avoid speculative abstractions; each layer has one clear reason to change.

## Consequences

Positive:
- Predictable memory behavior and horizontal scalability.
- Clear extension model for custom filters, search drivers, sort strategies, import maps, and exporters.

Trade-offs:
- More asynchronous flows than naive exports/imports.
- Requires strong observability to debug distributed chunk pipelines.

## Rejected Alternatives

- Single monolithic query/export service class: rejected due to high coupling and poor testability.
- OFFSET pagination for distributed exports: rejected due to performance and consistency issues at scale.
- Broker-specific queue integrations: rejected to preserve Laravel portability.
