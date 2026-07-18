<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Contracts;

use Yoosuf\LaravelDataFlow\DataTransferObjects\ExportPlan;

interface ExportCoordinatorContract
{
    public function dispatch(ExportPlan $plan): string;
}
