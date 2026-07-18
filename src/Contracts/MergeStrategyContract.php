<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Contracts;

use Yoosuf\LaravelDataFlow\DataTransferObjects\ExportTarget;
use Yoosuf\LaravelDataFlow\Enums\ExportFormat;

interface MergeStrategyContract
{
    /**
     * @param iterable<string> $chunkPaths
     */
    public function merge(ExportFormat $format, iterable $chunkPaths, ExportTarget $target): void;
}
