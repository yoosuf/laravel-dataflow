<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Exporting;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Yoosuf\LaravelDataFlow\DataTransferObjects\ExportChunk;
use Yoosuf\LaravelDataFlow\DataTransferObjects\ExportPlan;
use Yoosuf\LaravelDataFlow\DataTransferObjects\ExportTarget;
use Yoosuf\LaravelDataFlow\Query\QueryComposer;
use Yoosuf\LaravelDataFlow\Query\QueryComposition;
use Yoosuf\LaravelDataFlow\Support\MemoryGuard;

final class ExportChunkRunner
{
    public function __construct(
        private readonly QueryComposer $composer,
        private readonly ExporterFactory $exporters,
    ) {
    }

    public function run(ExportPlan $plan, ExportChunk $chunk): void
    {
        $baseQuery = $this->resolveBaseQuery($plan);
        $model = $baseQuery->getModel();
        $keyName = $model->getKeyName();

        $query = $this->composer->compose(
            $baseQuery,
            new QueryComposition(
                filters: $plan->filterGroup,
                search: $plan->search,
                sorts: $plan->sorts,
            ),
        )->whereBetween($keyName, [$chunk->fromId, $chunk->toId]);

        $exporter = $this->exporters->make($plan->format);
        $exporter->open(new ExportTarget($plan->target->disk, $chunk->path));

        $hydrateModels = (bool) config('dataflow.exports.hydrate_models', false);
        $cursor = $hydrateModels ? $query->cursor() : $query->toBase()->cursor();
        $memoryGuard = MemoryGuard::forExport();

        foreach ($cursor as $row) {
            $memoryGuard->tick();
            $exporter->writeRow($this->normalizeRow($row));
        }

        $exporter->close();
    }

    private function resolveBaseQuery(ExportPlan $plan): Builder
    {
        if ($plan->querySpecification !== null) {
            return $plan->querySpecification->toBuilder();
        }

        /** @var Model $model */
        $model = new $plan->modelClass();

        return $model->newQuery();
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
