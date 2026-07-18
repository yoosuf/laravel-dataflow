<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Yoosuf\LaravelDataFlow\DataFlowServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            DataFlowServiceProvider::class,
        ];
    }
}
