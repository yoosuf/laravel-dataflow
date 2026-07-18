<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\DataTransferObjects;

final readonly class SearchQuery
{
    /**
     * @param array<string> $terms
     * @param array<WeightedColumn> $columns
     * @param array<string> $relations
     */
    public function __construct(
        public array $terms,
        public array $columns = [],
        public array $relations = [],
    ) {
    }
}
