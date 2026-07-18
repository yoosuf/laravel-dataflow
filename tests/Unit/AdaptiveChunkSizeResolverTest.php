<?php

declare(strict_types=1);

use Yoosuf\LaravelDataFlow\DataTransferObjects\ChunkEstimationInput;
use Yoosuf\LaravelDataFlow\Exporting\Chunking\AdaptiveChunkSizeResolver;

it('resolves chunk size within configured min and max bounds', function (): void {
    $resolver = new AdaptiveChunkSizeResolver();

    $result = $resolver->resolve(new ChunkEstimationInput(
        memoryLimitBytes: 268435456,
        estimatedRowWidthBytes: 1024,
        databaseLatencyMs: 30,
        workerCount: 4,
        minimumChunkSize: 250,
        maximumChunkSize: 10000,
    ));

    expect($result->recommendedChunkSize)->toBeGreaterThanOrEqual(250);
    expect($result->recommendedChunkSize)->toBeLessThanOrEqual(10000);
});
