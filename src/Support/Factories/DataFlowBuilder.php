<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Support\Factories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Yoosuf\LaravelDataFlow\Contracts\DataFlowBuilderContract;
use Yoosuf\LaravelDataFlow\Contracts\FilterContract;
use Yoosuf\LaravelDataFlow\Contracts\ImportMapContract;
use Yoosuf\LaravelDataFlow\Contracts\ImportOperationContract;
use Yoosuf\LaravelDataFlow\Contracts\ExportOperationContract;
use Yoosuf\LaravelDataFlow\Contracts\ProgressStoreContract;
use Yoosuf\LaravelDataFlow\DataTransferObjects\FilterGroup;
use Yoosuf\LaravelDataFlow\DataTransferObjects\FilterRule;
use Yoosuf\LaravelDataFlow\DataTransferObjects\ImportPlan;
use Yoosuf\LaravelDataFlow\DataTransferObjects\ImportSource;
use Yoosuf\LaravelDataFlow\DataTransferObjects\SearchQuery;
use Yoosuf\LaravelDataFlow\DataTransferObjects\SortRule;
use Yoosuf\LaravelDataFlow\Enums\ExportFormat;
use Yoosuf\LaravelDataFlow\Enums\FilterOperator;
use Yoosuf\LaravelDataFlow\Enums\LogicalOperator;
use Yoosuf\LaravelDataFlow\Enums\SortDirection;
use Yoosuf\LaravelDataFlow\Exporting\ExportOperation;
use Yoosuf\LaravelDataFlow\Exporting\ExportRunner;
use Yoosuf\LaravelDataFlow\Exporting\ImportOperation;
use Yoosuf\LaravelDataFlow\Importing\ImportRunner;
use Yoosuf\LaravelDataFlow\Search\SearchQueryFactory;

final class DataFlowBuilder implements DataFlowBuilderContract
{
    /** @var array<class-string<FilterContract>> */
    private array $filters = [];

    private ?FilterGroup $filterGroup = null;

    private ?SearchQuery $searchQuery = null;

    /** @var array<SortRule> */
    private array $sorts = [];

    public function __construct(
        private readonly string $modelClass,
        private readonly ?Builder $baseQuery = null,
    )
    {
    }

    public function filters(array $filters): static
    {
        $this->filters = $filters;

        return $this;
    }

    public function fromRequest(Request|array $request): static
    {
        $payload = $request instanceof Request ? $request->all() : $request;

        $rules = [];

        foreach ((array) ($payload['filters'] ?? []) as $row) {
            if (! is_array($row)) {
                continue;
            }

            $field = (string) ($row['field'] ?? '');
            $operator = FilterOperator::tryFrom((string) ($row['operator'] ?? 'eq')) ?? FilterOperator::Eq;
            $value = $row['value'] ?? null;

            if ($field === '') {
                continue;
            }

            $rules[] = new FilterRule($field, $operator, $value);
        }

        $this->filterGroup = $rules !== [] ? new FilterGroup(LogicalOperator::And, $rules) : null;

        $this->search((string) ($payload['q'] ?? ''));
        $this->sort($payload['sort'] ?? null);

        return $this;
    }

    public function search(?string $term): static
    {
        $this->searchQuery = app(SearchQueryFactory::class)->fromTerm($term);

        return $this;
    }

    public function sort(string|array|null $sort): static
    {
        if ($sort === null || $sort === '' || $sort === []) {
            $this->sorts = [];

            return $this;
        }

        $tokens = is_array($sort) ? $sort : explode(',', $sort);

        $this->sorts = array_values(array_filter(array_map(static function ($token): ?SortRule {
            $raw = trim((string) $token);

            if ($raw === '') {
                return null;
            }

            $direction = str_starts_with($raw, '-') ? SortDirection::Desc : SortDirection::Asc;
            $field = ltrim($raw, '-+');

            if ($field === '') {
                return null;
            }

            return new SortRule($field, $direction);
        }, $tokens)));

        return $this;
    }

    public function export(ExportFormat|string $format): ExportOperationContract
    {
        $resolvedFormat = is_string($format) ? ExportFormat::from($format) : $format;

        return new ExportOperation(
            modelClass: $this->modelClass,
            baseQuery: $this->baseQuery,
            format: $resolvedFormat,
            filterGroup: $this->filterGroup,
            search: $this->searchQuery,
            sorts: $this->sorts,
            runner: app(ExportRunner::class),
        );
    }

    public function import(ImportSource $source, ImportMapContract $map): ImportOperationContract
    {
        return new ImportOperation(
            new ImportPlan(
                modelClass: $this->modelClass,
                source: $source,
                columns: $map->columns(),
                chunkSize: (int) config('dataflow.imports.chunk_size', 1000),
                errorPath: trim((string) config('dataflow.imports.error_prefix', 'imports/errors'), '/').'/'.str_replace('\\', '-', $this->modelClass).'-errors.json',
            ),
            app(ImportRunner::class),
            app(ProgressStoreContract::class),
            $this->baseQuery,
        );
    }
}
