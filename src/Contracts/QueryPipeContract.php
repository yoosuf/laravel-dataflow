<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Contracts;

use Closure;
use Illuminate\Database\Eloquent\Builder;

interface QueryPipeContract
{
    /**
     * @param Closure(Builder): Builder $next
     */
    public function handle(Builder $query, Closure $next): Builder;
}
