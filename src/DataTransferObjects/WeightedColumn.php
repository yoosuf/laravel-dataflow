<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\DataTransferObjects;

use InvalidArgumentException;

final readonly class WeightedColumn
{
    public function __construct(
        public string $name,
        public int $weight = 1,
    ) {
        if ($this->name === '') {
            throw new InvalidArgumentException('Column name cannot be empty.');
        }

        if ($this->weight < 1) {
            throw new InvalidArgumentException('Column weight must be greater than or equal to 1.');
        }
    }
}
