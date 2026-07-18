<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow;

use Illuminate\Database\Eloquent\Builder;
use Yoosuf\LaravelDataFlow\Contracts\DataFlowBuilderContract;
use Yoosuf\LaravelDataFlow\Contracts\DataFlowFactoryContract;

final class DataFlow
{
    public static function for(string $modelClass): DataFlowBuilderContract
    {
        return app(DataFlowFactoryContract::class)->for($modelClass);
    }

    public static function forQuery(Builder $query): DataFlowBuilderContract
    {
        return app(DataFlowFactoryContract::class)->forQuery($query);
    }
}
