<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Search;

use Illuminate\Database\Eloquent\Builder;
use Yoosuf\LaravelDataFlow\Contracts\SearchDriverContract;
use Yoosuf\LaravelDataFlow\DataTransferObjects\SearchQuery;

final class DatabaseLikeSearchDriver implements SearchDriverContract
{
    /**
     * @param array<string> $defaultColumns
     * @param array<string, array<string>> $relationColumns
     */
    public function __construct(
        private readonly array $defaultColumns,
        private readonly array $relationColumns,
    ) {
    }

    public function apply(Builder $query, SearchQuery $searchQuery): Builder
    {
        if ($searchQuery->terms === []) {
            return $query;
        }

        $columns = $searchQuery->columns !== []
            ? array_map(static fn ($column) => $column->name, $searchQuery->columns)
            : $this->defaultColumns;

        foreach ($searchQuery->terms as $term) {
            $query->where(function (Builder $nested) use ($columns, $searchQuery, $term): void {
                foreach ($columns as $column) {
                    $nested->orWhere($column, 'LIKE', '%'.$term.'%');
                }

                foreach ($searchQuery->relations as $relation) {
                    $relationColumns = $this->relationColumns[$relation] ?? [];

                    foreach ($relationColumns as $relationColumn) {
                        $nested->orWhereHas($relation, function (Builder $relationQuery) use ($relationColumn, $term): void {
                            $relationQuery->where($relationColumn, 'LIKE', '%'.$term.'%');
                        });
                    }
                }
            });
        }

        if ($searchQuery->columns !== []) {
            $this->applyWeightedOrder($query, $searchQuery);
        }

        return $query;
    }

    private function applyWeightedOrder(Builder $query, SearchQuery $searchQuery): void
    {
        $term = $searchQuery->terms[0] ?? null;

        if ($term === null) {
            return;
        }

        $parts = [];
        $bindings = [];

        foreach ($searchQuery->columns as $weightedColumn) {
            $parts[] = sprintf('CASE WHEN %s LIKE ? THEN %d ELSE 0 END', $weightedColumn->name, $weightedColumn->weight);
            $bindings[] = '%'.$term.'%';
        }

        if ($parts === []) {
            return;
        }

        $query->orderByRaw(implode(' + ', $parts).' DESC', $bindings);
    }
}
