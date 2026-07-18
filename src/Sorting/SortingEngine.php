<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Sorting;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Yoosuf\LaravelDataFlow\Contracts\SortStrategyContract;
use Yoosuf\LaravelDataFlow\DataTransferObjects\SortRule;
use Yoosuf\LaravelDataFlow\Exceptions\UnsupportedSortException;

final class SortingEngine
{
    public function __construct(private readonly SortAllowlist $allowlist)
    {
    }

    /**
     * @param array<SortRule> $rules
     */
    public function apply(Builder $query, array $rules): Builder
    {
        foreach ($rules as $rule) {
            $allowedSort = $this->allowlist->get($rule->field);

            if ($allowedSort === null) {
                throw UnsupportedSortException::forField($rule->field);
            }

            match ($allowedSort->type) {
                'column' => $query->orderBy($allowedSort->column, $rule->direction->value),
                'relation-subquery' => $query->orderBy(
                    DB::table((string) $allowedSort->table)
                        ->select((string) $allowedSort->column)
                        ->whereColumn((string) $allowedSort->ownerKey, (string) $allowedSort->foreignKey)
                        ->limit(1),
                    $rule->direction->value,
                ),
                'custom' => $this->applyCustomStrategy($query, (string) $allowedSort->strategy, $rule),
                default => throw UnsupportedSortException::forField($rule->field),
            };
        }

        return $query;
    }

    private function applyCustomStrategy(Builder $query, string $strategyClass, SortRule $rule): void
    {
        /** @var SortStrategyContract $strategy */
        $strategy = app($strategyClass);
        $strategy->apply($query, $rule);
    }
}
