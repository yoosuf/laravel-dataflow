<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Exporting;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Yoosuf\LaravelDataFlow\DataTransferObjects\ExportPlan;
use Yoosuf\LaravelDataFlow\Query\QueryComposer;
use Yoosuf\LaravelDataFlow\Query\QueryComposition;
use Yoosuf\LaravelDataFlow\Support\MemoryGuard;

final class ExportRunner
{
    public function __construct(
        private readonly QueryComposer $composer,
        private readonly ExporterFactory $exporters,
    ) {
    }

    public function run(ExportPlan $plan): void
    {
        if ($plan->querySpecification !== null) {
            $this->runWithQuery($plan, $plan->querySpecification->toBuilder());

            return;
        }

        /** @var Model $model */
        $model = new $plan->modelClass();
        $this->runWithQuery($plan, $model->newQuery());
    }

    public function runWithQuery(ExportPlan $plan, Builder $query): void
    {
        $composed = $this->composer->compose(
            $query,
            new QueryComposition(
                filters: $plan->filterGroup,
                search: $plan->search,
                sorts: $plan->sorts,
            ),
        );

        $exporter = $this->exporters->make($plan->format);
        $exporter->open($plan->target);

        $hydrateModels = (bool) config('dataflow.exports.hydrate_models', false);
        $cursor = $hydrateModels ? $composed->cursor() : $composed->toBase()->cursor();
        $memoryGuard = MemoryGuard::forExport();

        foreach ($cursor as $row) {
            $memoryGuard->tick();
            $exporter->writeRow($this->normalizeRow($row));
        }

        $exporter->close();
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeRow(mixed $row): array
    {
        if ($row instanceof Model) {
            return $row->toArray();
        }

        if (is_array($row)) {
            return $row;
        }

        if (is_object($row)) {
            return get_object_vars($row);
        }

        return ['value' => $row];
    }
}
