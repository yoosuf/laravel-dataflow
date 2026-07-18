<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Query;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Yoosuf\LaravelDataFlow\Contracts\FilterContract;
use Yoosuf\LaravelDataFlow\Contracts\QueryPipeContract;
use Yoosuf\LaravelDataFlow\Contracts\SearchDriverContract;
use Yoosuf\LaravelDataFlow\Query\Pipes\ApplyFiltersPipe;
use Yoosuf\LaravelDataFlow\Query\Pipes\ApplySearchPipe;
use Yoosuf\LaravelDataFlow\Query\Pipes\ApplySoftDeleteModePipe;
use Yoosuf\LaravelDataFlow\Query\Pipes\ApplySortingPipe;
use Yoosuf\LaravelDataFlow\Sorting\SortingEngine;

final class QueryComposer
{
    public function __construct(
        private readonly FilterContract $filters,
        private readonly SearchDriverContract $search,
        private readonly SortingEngine $sorting,
    ) {
    }

    public function compose(Builder $query, QueryComposition $composition): Builder
    {
        $pipes = [
            new ApplySoftDeleteModePipe($composition->softDeleteMode),
            new ApplyFiltersPipe($this->filters, $composition->filters),
            new ApplySearchPipe($this->search, $composition->search),
            new ApplySortingPipe($this->sorting, $composition->sorts),
        ];

        return $this->runPipeline($query, $pipes);
    }

    /**
     * @param array<QueryPipeContract> $pipes
     */
    private function runPipeline(Builder $query, array $pipes): Builder
    {
        $carry = static fn (Builder $builder): Builder => $builder;

        foreach (array_reverse($pipes) as $pipe) {
            $next = $carry;

            $carry = static fn (Builder $builder): Builder => $pipe->handle(
                $builder,
                static fn (Builder $innerBuilder): Builder => $next($innerBuilder),
            );
        }

        return $carry($query);
    }
}
