<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Contracts;

use Yoosuf\LaravelDataFlow\DataTransferObjects\ExportTarget;

interface ParquetWriterContract
{
    /**
     * @param iterable<array<string, mixed>> $rows
     */
    public function writeRows(iterable $rows, ExportTarget $target): void;
}
