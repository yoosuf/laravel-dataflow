<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\DataTransferObjects;

use InvalidArgumentException;
use Yoosuf\LaravelDataFlow\Enums\LogicalOperator;

final readonly class FilterGroup
{
    /**
     * @param array<FilterRule> $rules
     * @param array<FilterGroup> $groups
     */
    public function __construct(
        public LogicalOperator $boolean,
        public array $rules = [],
        public array $groups = [],
    ) {
        if ($this->rules === [] && $this->groups === []) {
            throw new InvalidArgumentException('A filter group must contain at least one rule or subgroup.');
        }
    }
}
