<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Events;

use Yoosuf\LaravelDataFlow\DataTransferObjects\ImportPlan;

final readonly class ImportCompleted
{
    public function __construct(
        public string $runId,
        public ImportPlan $plan,
    ) {
    }
}
