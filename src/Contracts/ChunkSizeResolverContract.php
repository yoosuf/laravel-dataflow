<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Contracts;

use Yoosuf\LaravelDataFlow\DataTransferObjects\ChunkEstimationInput;
use Yoosuf\LaravelDataFlow\DataTransferObjects\ChunkEstimationResult;

interface ChunkSizeResolverContract
{
    public function resolve(ChunkEstimationInput $input): ChunkEstimationResult;
}
