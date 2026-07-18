<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Contracts;

use Yoosuf\LaravelDataFlow\DataTransferObjects\ExportTarget;
use Yoosuf\LaravelDataFlow\Enums\ExportFormat;

interface ExporterContract
{
    public function format(): ExportFormat;

    public function open(ExportTarget $target): void;

    /**
     * @param array<string, mixed> $row
     */
    public function writeRow(array $row): void;

    public function close(): void;
}
