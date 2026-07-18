<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Filtering;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Yoosuf\LaravelDataFlow\Contracts\FilterContract;
use Yoosuf\LaravelDataFlow\DataTransferObjects\FilterGroup;
use Yoosuf\LaravelDataFlow\DataTransferObjects\FilterRule;
use Yoosuf\LaravelDataFlow\Enums\FilterOperator;
use Yoosuf\LaravelDataFlow\Enums\LogicalOperator;
use Yoosuf\LaravelDataFlow\Exceptions\UnsupportedFilterException;
use Yoosuf\LaravelDataFlow\Query\Support\JsonPathExpressionFactory;

final class AllowlistFilterEngine implements FilterContract
{
    public function __construct(
        private readonly FilterAllowlist $allowlist,
        private readonly JsonPathExpressionFactory $jsonExpression,
    ) {
    }

    public function apply(Builder $query, FilterGroup $group): Builder
    {
        $this->applyGroup($query, $group);

        return $query;
    }

    private function applyGroup(Builder $query, FilterGroup $group): void
    {
        $conditions = [];

        foreach ($group->rules as $rule) {
            $conditions[] = function (Builder $builder) use ($rule): void {
                $this->applyRule($builder, $rule);
            };
        }

        foreach ($group->groups as $child) {
            $conditions[] = function (Builder $builder) use ($child): void {
                $this->applyGroup($builder, $child);
            };
        }

        foreach ($conditions as $index => $condition) {
            if ($group->boolean === LogicalOperator::Or && $index > 0) {
                $query->orWhere(static function (Builder $nested) use ($condition): void {
                    $condition($nested);
                });

                continue;
            }

            $query->where(static function (Builder $nested) use ($condition): void {
                $condition($nested);
            });
        }
    }

    private function applyRule(Builder $query, FilterRule $rule): void
    {
        $definition = $this->allowlist->get($rule->field);

        if ($definition === null) {
            throw UnsupportedFilterException::forKey($rule->field);
        }

        match ($definition->type) {
            'column' => $this->applyColumn($query, $definition->column ?? '', $rule),
            'json' => $this->applyJson($query, $definition->column ?? '', $definition->jsonPath ?? '', $rule),
            'relation' => $this->applyRelation($query, $definition->relation ?? '', $definition->column ?? '', $rule),
            'relation-count' => $this->applyRelationCount($query, $definition->relation ?? '', $rule),
            'scope' => $this->applyScope($query, $definition->scope ?? '', $rule->value),
            default => throw UnsupportedFilterException::forKey($rule->field),
        };
    }

    private function applyColumn(Builder $query, string $column, FilterRule $rule): void
    {
        $this->applyCondition($query, $column, $rule->operator, $rule->value);
    }

    private function applyJson(Builder $query, string $column, string $jsonPath, FilterRule $rule): void
    {
        $expression = $this->jsonExpression->for($query, $column, $jsonPath);

        match ($rule->operator) {
            FilterOperator::Eq => $query->whereRaw("{$expression} = ?", [$rule->value]),
            FilterOperator::Neq => $query->whereRaw("{$expression} != ?", [$rule->value]),
            FilterOperator::Contains => $query->whereRaw("{$expression} LIKE ?", ['%'.$rule->value.'%']),
            FilterOperator::StartsWith => $query->whereRaw("{$expression} LIKE ?", [$rule->value.'%']),
            FilterOperator::EndsWith => $query->whereRaw("{$expression} LIKE ?", ['%'.$rule->value]),
            FilterOperator::IsNull => $query->whereRaw("{$expression} IS NULL"),
            FilterOperator::IsNotNull => $query->whereRaw("{$expression} IS NOT NULL"),
            FilterOperator::In => $query->whereIn(
                $query->getQuery()->raw($expression),
                Arr::wrap($rule->value),
            ),
            FilterOperator::NotIn => $query->whereNotIn(
                $query->getQuery()->raw($expression),
                Arr::wrap($rule->value),
            ),
            FilterOperator::Between, FilterOperator::DateRange => $query->whereRaw(
                "{$expression} BETWEEN ? AND ?",
                [Arr::get((array) $rule->value, 0), Arr::get((array) $rule->value, 1)],
            ),
        };
    }

    private function applyRelation(Builder $query, string $relation, string $column, FilterRule $rule): void
    {
        $query->whereHas($relation, function (Builder $relationQuery) use ($column, $rule): void {
            $this->applyCondition($relationQuery, $column, $rule->operator, $rule->value);
        });
    }

    private function applyRelationCount(Builder $query, string $relation, FilterRule $rule): void
    {
        $values = Arr::wrap($rule->value);

        match ($rule->operator) {
            FilterOperator::Eq => $query->has($relation, '=', (int) $rule->value),
            FilterOperator::Neq => $query->where(function (Builder $nested) use ($relation, $rule): void {
                $count = (int) $rule->value;

                $nested->has($relation, '<', $count)
                    ->orHas($relation, '>', $count);
            }),
            FilterOperator::Between, FilterOperator::DateRange => $query->has($relation, '>=', (int) Arr::get($values, 0))
                ->has($relation, '<=', (int) Arr::get($values, 1)),
            FilterOperator::In => $this->applyRelationCountIn($query, $relation, $values),
            FilterOperator::NotIn => $this->applyRelationCountNotIn($query, $relation, $values),
            default => $query->has($relation, '>=', 1),
        };
    }

    /**
     * @param array<int, mixed> $counts
     */
    private function applyRelationCountIn(Builder $query, string $relation, array $counts): void
    {
        $query->where(function (Builder $nested) use ($relation, $counts): void {
            foreach ($counts as $index => $count) {
                if ($index === 0) {
                    $nested->has($relation, '=', (int) $count);

                    continue;
                }

                $nested->orHas($relation, '=', (int) $count);
            }
        });
    }

    /**
     * @param array<int, mixed> $counts
     */
    private function applyRelationCountNotIn(Builder $query, string $relation, array $counts): void
    {
        foreach ($counts as $count) {
            $query->has($relation, '!=', (int) $count);
        }
    }

    private function applyScope(Builder $query, string $scope, mixed $value): void
    {
        if ($value === null) {
            $query->{$scope}();

            return;
        }

        if (is_array($value)) {
            $query->{$scope}(...$value);

            return;
        }

        $query->{$scope}($value);
    }

    private function applyCondition(Builder $query, string $column, FilterOperator $operator, mixed $value): void
    {
        match ($operator) {
            FilterOperator::Eq => $query->where($column, '=', $value),
            FilterOperator::Neq => $query->where($column, '!=', $value),
            FilterOperator::In => $query->whereIn($column, Arr::wrap($value)),
            FilterOperator::NotIn => $query->whereNotIn($column, Arr::wrap($value)),
            FilterOperator::Contains => $query->where($column, 'LIKE', '%'.$value.'%'),
            FilterOperator::StartsWith => $query->where($column, 'LIKE', $value.'%'),
            FilterOperator::EndsWith => $query->where($column, 'LIKE', '%'.$value),
            FilterOperator::Between, FilterOperator::DateRange => $query->whereBetween($column, [
                Arr::get((array) $value, 0),
                Arr::get((array) $value, 1),
            ]),
            FilterOperator::IsNull => $query->whereNull($column),
            FilterOperator::IsNotNull => $query->whereNotNull($column),
        };
    }
}
