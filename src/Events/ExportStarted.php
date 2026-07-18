<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Events;

use Yoosuf\LaravelDataFlow\DataTransferObjects\ExportPlan;

final readonly class ExportStarted
{
    public function __construct(
        public string $runId,
        public ExportPlan $plan,
    ) {
    }
}
