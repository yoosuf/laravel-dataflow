<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Exporting\Chunking;

use Yoosuf\LaravelDataFlow\Contracts\ChunkSizeResolverContract;
use Yoosuf\LaravelDataFlow\DataTransferObjects\ChunkEstimationInput;
use Yoosuf\LaravelDataFlow\DataTransferObjects\ChunkEstimationResult;

final class AdaptiveChunkSizeResolver implements ChunkSizeResolverContract
{
    public function resolve(ChunkEstimationInput $input): ChunkEstimationResult
    {
        $safeMemoryBudget = (int) floor($input->memoryLimitBytes * 0.4);
        $rowWidth = max($input->estimatedRowWidthBytes, 1);
        $workers = max($input->workerCount, 1);

        $base = (int) floor($safeMemoryBudget / ($rowWidth * $workers));

        // Favor smaller chunks under high latency to reduce worker idle recovery costs.
        if ($input->databaseLatencyMs > 80) {
            $base = (int) floor($base * 0.7);
        }

        $recommended = max($input->minimumChunkSize, min($base, $input->maximumChunkSize));

        return new ChunkEstimationResult(
            recommendedChunkSize: $recommended,
            reason: sprintf(
                'Resolved from memory=%d,row=%d,latency=%.2f,workers=%d',
                $input->memoryLimitBytes,
                $input->estimatedRowWidthBytes,
                $input->databaseLatencyMs,
                $input->workerCount,
            ),
        );
    }
}
