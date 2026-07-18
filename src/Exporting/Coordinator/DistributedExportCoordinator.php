<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Exporting\Coordinator;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;
use Yoosuf\LaravelDataFlow\Contracts\ChunkSizeResolverContract;
use Yoosuf\LaravelDataFlow\Contracts\ExportCoordinatorContract;
use Yoosuf\LaravelDataFlow\Contracts\ProgressStoreContract;
use Yoosuf\LaravelDataFlow\DataTransferObjects\ChunkEstimationInput;
use Yoosuf\LaravelDataFlow\DataTransferObjects\ExportChunk;
use Yoosuf\LaravelDataFlow\DataTransferObjects\ExportPlan;
use Yoosuf\LaravelDataFlow\DataTransferObjects\ProgressSnapshot;
use Yoosuf\LaravelDataFlow\Enums\ExportFormat;
use Yoosuf\LaravelDataFlow\Enums\RunStatus;
use Yoosuf\LaravelDataFlow\Jobs\MergeExportChunksJob;
use Yoosuf\LaravelDataFlow\Jobs\RunExportChunkJob;
use Yoosuf\LaravelDataFlow\Jobs\RunExportJob;
use Yoosuf\LaravelDataFlow\Query\QueryComposer;
use Yoosuf\LaravelDataFlow\Query\QueryComposition;

final class DistributedExportCoordinator implements ExportCoordinatorContract
{
    public function __construct(
        private readonly QueryComposer $composer,
        private readonly ChunkSizeResolverContract $chunkResolver,
        private readonly ProgressStoreContract $progress,
    ) {
    }

    public function dispatch(ExportPlan $plan): string
    {
        $runId = Str::uuid()->toString();
        $queue = (string) config('dataflow.exports.queue', 'default');

        $query = $this->baseQuery($plan);
        $totalRows = (int) (clone $query)->count();

        $this->progress->put(new ProgressSnapshot(
            runId: $runId,
            status: RunStatus::Pending,
            processedRows: 0,
            failedRows: 0,
            totalRows: $totalRows,
        ));

        $threshold = (int) config('dataflow.exports.distributed_threshold', 50000);

        if ($totalRows <= $threshold || $plan->format === ExportFormat::Json) {
            Bus::dispatch((new RunExportJob($plan))->onQueue($queue));

            return $runId;
        }

        $chunkSize = $this->chunkResolver->resolve(new ChunkEstimationInput(
            memoryLimitBytes: (int) config('dataflow.chunking.memory_limit_bytes', 268435456),
            estimatedRowWidthBytes: (int) config('dataflow.chunking.estimated_row_width_bytes', 1024),
            databaseLatencyMs: (float) config('dataflow.chunking.database_latency_ms', 40),
            workerCount: (int) config('dataflow.chunking.worker_count', 4),
            minimumChunkSize: (int) config('dataflow.chunking.min_size', 250),
            maximumChunkSize: (int) config('dataflow.chunking.max_size', 10000),
        ))->recommendedChunkSize;

        $chunks = $this->planChunks($plan, $query, $chunkSize, $runId);

        $jobs = [];
        $chunkPaths = [];

        foreach ($chunks as $chunk) {
            $jobs[] = (new RunExportChunkJob($plan, $chunk))->onQueue($queue);
            $chunkPaths[] = $chunk->path;
        }

        $jobs[] = (new MergeExportChunksJob($runId, $plan, $chunkPaths))->onQueue($queue);

        Bus::chain($jobs)->dispatch();

        $this->progress->put(new ProgressSnapshot(
            runId: $runId,
            status: RunStatus::Running,
            processedRows: 0,
            failedRows: 0,
            totalRows: $totalRows,
        ));

        return $runId;
    }

    private function baseQuery(ExportPlan $plan): Builder
    {
        if ($plan->querySpecification !== null) {
            return $this->composer->compose(
                $plan->querySpecification->toBuilder(),
                new QueryComposition(
                    filters: $plan->filterGroup,
                    search: $plan->search,
                    sorts: $plan->sorts,
                ),
            );
        }

        /** @var Model $model */
        $model = new $plan->modelClass();

        return $this->composer->compose(
            $model->newQuery(),
            new QueryComposition(
                filters: $plan->filterGroup,
                search: $plan->search,
                sorts: $plan->sorts,
            ),
        );
    }

    /**
     * @return array<ExportChunk>
     */
    private function planChunks(ExportPlan $plan, Builder $query, int $chunkSize, string $runId): array
    {
        /** @var Model $model */
        $model = $query->getModel();
        $keyName = $model->getKeyName();

        $tempPrefix = trim((string) config('dataflow.exports.temp_prefix', 'exports/temp'), '/');
        $chunks = [];

        $chunkIndex = 0;
        (clone $query)
            ->select($model->qualifyColumn($keyName))
            ->orderBy($keyName)
            ->chunkById($chunkSize, function ($rows) use (&$chunks, &$chunkIndex, $tempPrefix, $runId, $plan, $keyName): void {
                $first = $rows->first();
                $last = $rows->last();

                if ($first === null || $last === null) {
                    return;
                }

                $chunkIndex++;
                $chunks[] = new ExportChunk(
                    index: $chunkIndex,
                    fromId: (int) $first->{$keyName},
                    toId: (int) $last->{$keyName},
                    path: sprintf('%s/%s/chunk-%05d.%s', $tempPrefix, $runId, $chunkIndex, $plan->format->value),
                );
            }, $keyName);

        return $chunks;
    }
}
