<?php

declare(strict_types=1);

use Yoosuf\LaravelDataFlow\DataTransferObjects\ColumnMap;
use Yoosuf\LaravelDataFlow\DataTransferObjects\FilterGroup;
use Yoosuf\LaravelDataFlow\DataTransferObjects\FilterRule;
use Yoosuf\LaravelDataFlow\DataTransferObjects\SortRule;
use Yoosuf\LaravelDataFlow\DataTransferObjects\WeightedColumn;
use Yoosuf\LaravelDataFlow\Enums\FilterOperator;
use Yoosuf\LaravelDataFlow\Enums\LogicalOperator;
use Yoosuf\LaravelDataFlow\Enums\SortDirection;

it('rejects invalid dto arguments', function (): void {
    expect(fn (): FilterRule => new FilterRule('', FilterOperator::Eq, 'value'))->toThrow(InvalidArgumentException::class);
    expect(fn (): SortRule => new SortRule('', SortDirection::Asc))->toThrow(InvalidArgumentException::class);
    expect(fn (): WeightedColumn => new WeightedColumn('', 1))->toThrow(InvalidArgumentException::class);
    expect(fn (): WeightedColumn => new WeightedColumn('name', 0))->toThrow(InvalidArgumentException::class);
    expect(fn (): ColumnMap => new ColumnMap('email', ''))->toThrow(InvalidArgumentException::class);
    expect(fn (): FilterGroup => new FilterGroup(LogicalOperator::And))->toThrow(InvalidArgumentException::class);
});

it('stores valid dto values', function (): void {
    $rule = new FilterRule('status', FilterOperator::Eq, 'active');
    $group = new FilterGroup(LogicalOperator::And, [$rule]);

    expect($group->rules)->toHaveCount(1);
    expect($group->rules[0]->field)->toBe('status');
});
