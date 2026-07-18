<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Sorting;

use InvalidArgumentException;

final readonly class AllowedSort
{
    private function __construct(
        public string $key,
        public string $type,
        public ?string $column = null,
        public ?string $table = null,
        public ?string $ownerKey = null,
        public ?string $foreignKey = null,
        public ?string $strategy = null,
    ) {
        if ($this->key === '') {
            throw new InvalidArgumentException('Allowed sort key cannot be empty.');
        }
    }

    public static function column(string $key, string $column): self
    {
        return new self($key, 'column', column: $column);
    }

    public static function relationSubquery(
        string $key,
        string $table,
        string $ownerKey,
        string $foreignKey,
        string $column,
    ): self {
        return new self(
            $key,
            'relation-subquery',
            column: $column,
            table: $table,
            ownerKey: $ownerKey,
            foreignKey: $foreignKey,
        );
    }

    public static function custom(string $key, string $strategy): self
    {
        return new self($key, 'custom', strategy: $strategy);
    }
}
