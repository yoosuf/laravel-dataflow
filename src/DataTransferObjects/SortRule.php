<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\DataTransferObjects;

use InvalidArgumentException;
use Yoosuf\LaravelDataFlow\Enums\SortDirection;

final readonly class SortRule
{
    public function __construct(
        public string $field,
        public SortDirection $direction,
    ) {
        if ($this->field === '') {
            throw new InvalidArgumentException('Sort field cannot be empty.');
        }
    }
}
