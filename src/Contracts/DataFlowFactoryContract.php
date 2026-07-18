<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface DataFlowFactoryContract
{
    public function for(string $modelClass): DataFlowBuilderContract;

    public function forQuery(Builder $query): DataFlowBuilderContract;
}
