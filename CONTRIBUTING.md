# Contributing

Thanks for your interest in improving laravel-dataflow.

## Ground Rules

- Be respectful and constructive in all interactions.
- Follow the Code of Conduct in CODE_OF_CONDUCT.md.
- Keep pull requests focused and minimal.

## Development Setup

1. Fork and clone the repository.
2. Install dependencies:

   composer install

3. Run quality checks before opening a pull request:

   composer lint
   composer analyse
   composer test

## Branching and Commits

- Create a feature branch from main.
- Use clear commit messages (Conventional Commit style is recommended).
- Keep history clean and avoid unrelated changes in a single pull request.

## Pull Request Expectations

- Explain what changed and why.
- Include tests for behavior changes.
- Update documentation when public behavior or configuration changes.
- Avoid committing generated benchmark output artifacts.

## Documentation Changes

When changing configuration, commands, or runtime behavior, update:

- README.md
- docs/PERFORMANCE_TUNING.md
- CHANGELOG.md (for release-bound changes)

## Reporting Bugs

Please open an issue using the bug report template and include:

- Laravel version
- PHP version
- Package version
- Minimal reproduction steps
- Expected vs actual behavior

## Questions and Support

See SUPPORT.md for support channels.
