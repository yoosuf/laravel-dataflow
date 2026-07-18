<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\DataTransferObjects;

use InvalidArgumentException;
use Yoosuf\LaravelDataFlow\Enums\FilterOperator;

final readonly class FilterRule
{
    public function __construct(
        public string $field,
        public FilterOperator $operator,
        public mixed $value,
    ) {
        if ($this->field === '') {
            throw new InvalidArgumentException('Filter field cannot be empty.');
        }
    }
}
