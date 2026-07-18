<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\DataTransferObjects;

use Yoosuf\LaravelDataFlow\Enums\RunStatus;

final readonly class ProgressSnapshot
{
    public function __construct(
        public string $runId,
        public RunStatus $status,
        public int $processedRows,
        public int $failedRows,
        public ?int $totalRows = null,
        public ?int $etaSeconds = null,
    ) {
    }
}
