<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\DataTransferObjects;

final readonly class ImportPlan
{
    /**
     * @param array<ColumnMap> $columns
     */
    public function __construct(
        public string $modelClass,
        public ImportSource $source,
        public array $columns,
        public bool $upsert = false,
        public array $upsertBy = [],
        public array $updateColumns = [],
        public int $chunkSize = 1000,
        public string $errorPath = 'imports/errors.json',
    ) {
    }
}
