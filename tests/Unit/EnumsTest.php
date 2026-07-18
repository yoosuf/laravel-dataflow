<?php

declare(strict_types=1);

use Yoosuf\LaravelDataFlow\Enums\ExportFormat;
use Yoosuf\LaravelDataFlow\Enums\FilterOperator;
use Yoosuf\LaravelDataFlow\Enums\ImportFormat;
use Yoosuf\LaravelDataFlow\Enums\LogicalOperator;
use Yoosuf\LaravelDataFlow\Enums\RunStatus;
use Yoosuf\LaravelDataFlow\Enums\SoftDeleteMode;
use Yoosuf\LaravelDataFlow\Enums\SortDirection;

it('defines expected filter operators', function (): void {
    expect(array_map(static fn (FilterOperator $op): string => $op->value, FilterOperator::cases()))
        ->toContain('eq', 'neq', 'in', 'not-in', 'contains', 'starts-with', 'ends-with', 'between', 'date-range', 'null', 'not-null');
});

it('defines expected export and import formats', function (): void {
    expect(array_map(static fn (ExportFormat $format): string => $format->value, ExportFormat::cases()))
        ->toContain('csv', 'xlsx', 'pdf', 'json', 'ndjson', 'xml', 'parquet');

    expect(array_map(static fn (ImportFormat $format): string => $format->value, ImportFormat::cases()))
        ->toContain('csv', 'xlsx', 'json', 'ndjson');
});

it('defines expected logical, sorting, status, and soft-delete values', function (): void {
    expect(array_map(static fn (LogicalOperator $op): string => $op->value, LogicalOperator::cases()))
        ->toBe(['and', 'or']);

    expect(array_map(static fn (SortDirection $direction): string => $direction->value, SortDirection::cases()))
        ->toBe(['asc', 'desc']);

    expect(array_map(static fn (RunStatus $status): string => $status->value, RunStatus::cases()))
        ->toContain('pending', 'running', 'completed', 'failed', 'cancelled');

    expect(array_map(static fn (SoftDeleteMode $mode): string => $mode->value, SoftDeleteMode::cases()))
        ->toContain('without-trashed', 'with-trashed', 'only-trashed');
});
