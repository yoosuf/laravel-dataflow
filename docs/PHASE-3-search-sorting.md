# Phase 3: Search + Sorting Engines

## What was delivered

- Database LIKE search driver with:
  - multi-keyword term handling
  - optional relation search (`orWhereHas`)
  - optional weighted columns for relevance ordering
- Sort allowlist engine with:
  - direct column sorting
  - relation-subquery sorting (no join duplication)
  - custom sort strategy class support
- Query composer integration:
  - `ApplySearchPipe`
  - `ApplySortingPipe`

## Configuration

```php
'search' => [
    'columns' => ['name', 'email'],
    'relations' => [
        'posts' => ['title'],
    ],
],

'sorting' => [
    'allowlist' => [
        'name' => 'name',
        'company.name' => [
            'type' => 'relation-subquery',
            'table' => 'companies',
            'owner_key' => 'companies.id',
            'foreign_key' => 'users.company_id',
            'column' => 'name',
        ],
        'name_length' => [
            'type' => 'custom',
            'strategy' => App\\DataFlow\\SortStrategies\\NameLengthSortStrategy::class,
        ],
    ],
],
```

## Usage

```php
use Yoosuf\LaravelDataFlow\DataTransferObjects\SearchQuery;
use Yoosuf\LaravelDataFlow\DataTransferObjects\SortRule;
use Yoosuf\LaravelDataFlow\Enums\SortDirection;
use Yoosuf\LaravelDataFlow\Query\QueryComposer;
use Yoosuf\LaravelDataFlow\Query\QueryComposition;

$composition = new QueryComposition(
    search: new SearchQuery(
        terms: ['vip', 'launch'],
        relations: ['posts'],
    ),
    sorts: [
        new SortRule('company.name', SortDirection::Asc),
    ],
);

$query = app(QueryComposer::class)->compose(User::query(), $composition);
```

## Security

- Sort keys must be allowlisted.
- Unknown sort keys throw `UnsupportedSortException`.
- Search columns are constrained to configured lists.

## Tests

Feature coverage lives in:
- `tests/Feature/SearchSortingQueryComposerTest.php`
