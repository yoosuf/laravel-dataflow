# Phase 2: Filtering Engine + Query Composer

## What was delivered

- Allowlist-based filtering engine with explicit key registration
- Nested logical groups (`and` / `or`)
- Filter operators: eq, neq, in, not-in, contains, starts-with, ends-with, between, date-range, null, not-null
- Relationship filters via `whereHas`
- Relationship count filters via `has` semantics
- Model scope filters (`scope` type)
- JSON path filtering through dialect-aware expression factory
- Query composition pipeline with soft-delete mode pipe + filter pipe

## Configuration

```php
// config/dataflow.php
'filters' => [
    'allowlist' => [
        'status' => 'status',
        'profile.role' => ['type' => 'json', 'column' => 'profile', 'path' => 'role'],
        'posts.title' => ['type' => 'relation', 'relation' => 'posts', 'column' => 'title'],
        'posts_count' => ['type' => 'relation-count', 'relation' => 'posts'],
        'tenant' => ['type' => 'scope', 'scope' => 'tenant'],
    ],
],
```

## Usage

```php
use Yoosuf\LaravelDataFlow\DataTransferObjects\FilterGroup;
use Yoosuf\LaravelDataFlow\DataTransferObjects\FilterRule;
use Yoosuf\LaravelDataFlow\Enums\FilterOperator;
use Yoosuf\LaravelDataFlow\Enums\LogicalOperator;
use Yoosuf\LaravelDataFlow\Enums\SoftDeleteMode;
use Yoosuf\LaravelDataFlow\Query\QueryComposer;
use Yoosuf\LaravelDataFlow\Query\QueryComposition;

$group = new FilterGroup(
    LogicalOperator::And,
    rules: [
        new FilterRule('status', FilterOperator::Eq, 'active'),
        new FilterRule('tenant', FilterOperator::Eq, 10),
    ],
    groups: [
        new FilterGroup(LogicalOperator::Or, rules: [
            new FilterRule('posts.title', FilterOperator::Contains, 'vip'),
            new FilterRule('profile.role', FilterOperator::Eq, 'admin'),
        ]),
    ],
);

$query = app(QueryComposer::class)->compose(
    User::query(),
    new QueryComposition($group, SoftDeleteMode::WithoutTrashed),
);
```

## Security

Only keys in `filters.allowlist` are accepted. Unregistered filter keys raise `UnsupportedFilterException`.

## Tests

Feature coverage lives in:
- `tests/Feature/FilteringQueryComposerTest.php`

The tests cover:
- Nested groups and relation/scope filters
- Allowlist rejection behavior
- JSON/date-range/relation-count behavior
- Soft-delete mode behavior
