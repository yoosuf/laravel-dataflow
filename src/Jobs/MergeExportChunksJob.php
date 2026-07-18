<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Yoosuf\LaravelDataFlow\Contracts\MergeStrategyContract;
use Yoosuf\LaravelDataFlow\Contracts\ProgressStoreContract;
use Yoosuf\LaravelDataFlow\DataTransferObjects\ExportPlan;
use Yoosuf\LaravelDataFlow\DataTransferObjects\ProgressSnapshot;
use Yoosuf\LaravelDataFlow\Enums\RunStatus;

final class MergeExportChunksJob implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param array<string> $chunkPaths
     */
    public function __construct(
        public readonly string $runId,
        public readonly ExportPlan $plan,
        public readonly array $chunkPaths,
    ) {
    }

    public function handle(MergeStrategyContract $mergeStrategy, ProgressStoreContract $progress): void
    {
        $mergeStrategy->merge($this->plan->format, $this->chunkPaths, $this->plan->target);

        $disk = Storage::disk($this->plan->target->disk);
        foreach ($this->chunkPaths as $path) {
            if ($disk->exists($path)) {
                $disk->delete($path);
            }
        }

        $progress->put(new ProgressSnapshot(
            runId: $this->runId,
            status: RunStatus::Completed,
            processedRows: 0,
            failedRows: 0,
        ));
    }
}
