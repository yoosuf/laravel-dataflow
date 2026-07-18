# ADR-002: Public API Contracts and Value Types

- Status: Accepted
- Date: 2026-07-18

## Context

Phase 1 defines the public API shape before implementation. We need strong extension points without over-abstraction.

## Decision

The package exposes two contract groups:

1. Fluent consumer API contracts:
- `DataFlowFactoryContract`
- `DataFlowBuilderContract`
- `ExportOperationContract`
- `ImportOperationContract`

2. Extension contracts:
- Filtering, search, sorting, query pipes
- Exporter and import reader/map contracts
- Coordinator, merge strategy, chunk resolver, progress store

All cross-layer payloads are readonly DTOs and enums.

## KISS Check

- No contract was added for classes with a single likely implementation in the foreseeable roadmap.
- Contracts exist only where users or package internals need pluggable behavior.
- DTOs are plain readonly carriers with minimal guards, not behavior-rich service objects.

## SOLID / DRY Notes

- DIP: engines depend on contracts, not concrete drivers.
- ISP: contracts remain focused and small.
- DRY: shared payload types (plans, rules, progress) avoid duplicate array shape handling.

## Consequences

Positive:
- Stable API boundaries for future modules.
- Better static analysis and discoverability.

Trade-off:
- Requires careful semver discipline once implementations arrive.
