<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Support\Factories;

use Illuminate\Database\Eloquent\Builder;
use Yoosuf\LaravelDataFlow\Contracts\DataFlowBuilderContract;
use Yoosuf\LaravelDataFlow\Contracts\DataFlowFactoryContract;

final class DataFlowFactory implements DataFlowFactoryContract
{
    public function for(string $modelClass): DataFlowBuilderContract
    {
        return app(DataFlowBuilder::class, ['modelClass' => $modelClass]);
    }

    public function forQuery(Builder $query): DataFlowBuilderContract
    {
        return app(DataFlowBuilder::class, [
            'modelClass' => $query->getModel()::class,
            'baseQuery' => $query,
        ]);
    }
}
