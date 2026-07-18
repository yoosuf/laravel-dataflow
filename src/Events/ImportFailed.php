<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Events;

use Throwable;
use Yoosuf\LaravelDataFlow\DataTransferObjects\ImportPlan;

final readonly class ImportFailed
{
    public function __construct(
        public string $runId,
        public ImportPlan $plan,
        public Throwable $exception,
    ) {
    }
}
