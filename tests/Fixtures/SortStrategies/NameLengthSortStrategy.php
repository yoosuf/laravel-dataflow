<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Tests\Fixtures\SortStrategies;

use Illuminate\Database\Eloquent\Builder;
use Yoosuf\LaravelDataFlow\Contracts\SortStrategyContract;
use Yoosuf\LaravelDataFlow\DataTransferObjects\SortRule;

final class NameLengthSortStrategy implements SortStrategyContract
{
    public function apply(Builder $query, SortRule $rule): Builder
    {
        return $query->orderByRaw('LENGTH(name) '.$rule->direction->value);
    }
}
