<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Filtering;

use InvalidArgumentException;

final readonly class AllowedFilter
{
    private function __construct(
        public string $key,
        public string $type,
        public ?string $column = null,
        public ?string $relation = null,
        public ?string $jsonPath = null,
        public ?string $scope = null,
    ) {
        if ($this->key === '') {
            throw new InvalidArgumentException('Allowed filter key cannot be empty.');
        }
    }

    public static function column(string $key, string $column): self
    {
        return new self($key, 'column', column: $column);
    }

    public static function json(string $key, string $column, string $jsonPath): self
    {
        return new self($key, 'json', column: $column, jsonPath: $jsonPath);
    }

    public static function relation(string $key, string $relation, string $column): self
    {
        return new self($key, 'relation', column: $column, relation: $relation);
    }

    public static function relationCount(string $key, string $relation): self
    {
        return new self($key, 'relation-count', relation: $relation);
    }

    public static function scope(string $key, string $scope): self
    {
        return new self($key, 'scope', scope: $scope);
    }
}
