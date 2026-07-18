<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Query\Pipes;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Yoosuf\LaravelDataFlow\Contracts\QueryPipeContract;
use Yoosuf\LaravelDataFlow\DataTransferObjects\SortRule;
use Yoosuf\LaravelDataFlow\Sorting\SortingEngine;

final class ApplySortingPipe implements QueryPipeContract
{
    /**
     * @param array<SortRule> $sorts
     */
    public function __construct(
        private readonly SortingEngine $sorting,
        private readonly array $sorts,
    ) {
    }

    public function handle(Builder $query, Closure $next): Builder
    {
        if ($this->sorts !== []) {
            $this->sorting->apply($query, $this->sorts);
        }

        return $next($query);
    }
}
