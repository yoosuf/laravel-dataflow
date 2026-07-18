<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Concerns;

use Yoosuf\LaravelDataFlow\Contracts\DataFlowBuilderContract;
use Yoosuf\LaravelDataFlow\DataFlow;

trait HasDataFlow
{
    public static function dataFlow(): DataFlowBuilderContract
    {
        return DataFlow::for(static::class);
    }
}
