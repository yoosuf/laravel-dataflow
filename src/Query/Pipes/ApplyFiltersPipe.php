<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Query\Pipes;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Yoosuf\LaravelDataFlow\Contracts\FilterContract;
use Yoosuf\LaravelDataFlow\Contracts\QueryPipeContract;
use Yoosuf\LaravelDataFlow\DataTransferObjects\FilterGroup;

final class ApplyFiltersPipe implements QueryPipeContract
{
    public function __construct(
        private readonly FilterContract $filters,
        private readonly ?FilterGroup $group,
    ) {
    }

    public function handle(Builder $query, Closure $next): Builder
    {
        if ($this->group !== null) {
            $this->filters->apply($query, $this->group);
        }

        return $next($query);
    }
}
