<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Query\Pipes;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Yoosuf\LaravelDataFlow\Contracts\QueryPipeContract;
use Yoosuf\LaravelDataFlow\Enums\SoftDeleteMode;

final class ApplySoftDeleteModePipe implements QueryPipeContract
{
    public function __construct(private readonly SoftDeleteMode $mode)
    {
    }

    public function handle(Builder $query, Closure $next): Builder
    {
        if (! method_exists($query->getModel(), 'bootSoftDeletes')) {
            return $next($query);
        }

        if ($this->mode === SoftDeleteMode::WithTrashed) {
            $query->withTrashed();
        }

        if ($this->mode === SoftDeleteMode::OnlyTrashed) {
            $query->onlyTrashed();
        }

        return $next($query);
    }
}
