<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Search;

use Yoosuf\LaravelDataFlow\DataTransferObjects\SearchQuery;

final class SearchQueryFactory
{
    /**
     * @param array<string> $relations
     */
    public function fromTerm(?string $term, array $relations = []): SearchQuery
    {
        if ($term === null || trim($term) === '') {
            return new SearchQuery([]);
        }

        $parts = preg_split('/\s+/', trim($term));

        return new SearchQuery(
            terms: array_values(array_filter($parts !== false ? $parts : [])),
            relations: $relations,
        );
    }
}
