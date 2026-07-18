<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Query;

use Yoosuf\LaravelDataFlow\DataTransferObjects\FilterGroup;
use Yoosuf\LaravelDataFlow\DataTransferObjects\SearchQuery;
use Yoosuf\LaravelDataFlow\DataTransferObjects\SortRule;
use Yoosuf\LaravelDataFlow\Enums\SoftDeleteMode;

final readonly class QueryComposition
{
    /**
     * @param array<SortRule> $sorts
     */
    public function __construct(
        public ?FilterGroup $filters = null,
        public ?SearchQuery $search = null,
        public array $sorts = [],
        public SoftDeleteMode $softDeleteMode = SoftDeleteMode::WithoutTrashed,
    ) {
    }
}
