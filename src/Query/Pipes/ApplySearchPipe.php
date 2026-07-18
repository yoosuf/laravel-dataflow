<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Query\Pipes;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Yoosuf\LaravelDataFlow\Contracts\QueryPipeContract;
use Yoosuf\LaravelDataFlow\Contracts\SearchDriverContract;
use Yoosuf\LaravelDataFlow\DataTransferObjects\SearchQuery;

final class ApplySearchPipe implements QueryPipeContract
{
    public function __construct(
        private readonly SearchDriverContract $search,
        private readonly ?SearchQuery $searchQuery,
    ) {
    }

    public function handle(Builder $query, Closure $next): Builder
    {
        if ($this->searchQuery !== null) {
            $this->search->apply($query, $this->searchQuery);
        }

        return $next($query);
    }
}
