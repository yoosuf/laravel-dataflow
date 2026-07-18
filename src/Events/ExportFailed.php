<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Events;

use Throwable;
use Yoosuf\LaravelDataFlow\DataTransferObjects\ExportPlan;

final readonly class ExportFailed
{
    public function __construct(
        public string $runId,
        public ExportPlan $plan,
        public Throwable $exception,
    ) {
    }
}
