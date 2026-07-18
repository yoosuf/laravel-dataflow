<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\DataTransferObjects;

final readonly class ChunkEstimationResult
{
    public function __construct(
        public int $recommendedChunkSize,
        public string $reason,
    ) {
    }
}
