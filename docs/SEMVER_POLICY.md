# SemVer Policy

This package follows Semantic Versioning.

## Versioning Rules

- MAJOR: incompatible public API changes
- MINOR: backward-compatible features
- PATCH: backward-compatible fixes

## Public API Surface

Public API includes:

- `Yoosuf\\LaravelDataFlow\\Contracts\\*`
- `Yoosuf\\LaravelDataFlow\\DataTransferObjects\\*`
- `Yoosuf\\LaravelDataFlow\\Enums\\*`
- documented config keys and route/command names

Internal implementation classes may change without deprecation unless explicitly documented as public extension points.
