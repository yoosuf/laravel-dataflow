<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\DataTransferObjects;

final readonly class ChunkEstimationInput
{
    public function __construct(
        public int $memoryLimitBytes,
        public int $estimatedRowWidthBytes,
        public float $databaseLatencyMs,
        public int $workerCount,
        public int $minimumChunkSize,
        public int $maximumChunkSize,
    ) {
    }
}
