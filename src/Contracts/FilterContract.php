<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Yoosuf\LaravelDataFlow\DataTransferObjects\FilterGroup;

interface FilterContract
{
    public function apply(Builder $query, FilterGroup $group): Builder;
}
