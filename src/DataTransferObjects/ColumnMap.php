<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\DataTransferObjects;

use InvalidArgumentException;

final readonly class ColumnMap
{
    /**
     * @param array<class-string> $transformers
     */
    public function __construct(
        public string|int $source,
        public string $target,
        public bool $required = false,
        public mixed $defaultValue = null,
        public array $transformers = [],
    ) {
        if ($this->target === '') {
            throw new InvalidArgumentException('Column target cannot be empty.');
        }
    }
}
