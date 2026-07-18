<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\DataTransferObjects;

use Yoosuf\LaravelDataFlow\Enums\ExportFormat;

final readonly class ExportPlan
{
    /**
     * @param array<class-string> $filters
     * @param array<SortRule> $sorts
     */
    public function __construct(
        public string $modelClass,
        public ExportFormat $format,
        public ExportTarget $target,
        public ?FilterGroup $filterGroup = null,
        public array $filters = [],
        public ?SearchQuery $search = null,
        public array $sorts = [],
        public ?QuerySpecification $querySpecification = null,
    ) {
    }
}
