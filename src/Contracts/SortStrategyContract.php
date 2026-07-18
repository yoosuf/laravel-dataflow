<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Yoosuf\LaravelDataFlow\DataTransferObjects\SortRule;

interface SortStrategyContract
{
    public function apply(Builder $query, SortRule $rule): Builder;
}
