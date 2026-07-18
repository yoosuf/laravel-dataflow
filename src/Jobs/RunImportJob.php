<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Event;
use Throwable;
use Yoosuf\LaravelDataFlow\Contracts\ProgressStoreContract;
use Yoosuf\LaravelDataFlow\DataTransferObjects\ImportPlan;
use Yoosuf\LaravelDataFlow\DataTransferObjects\ProgressSnapshot;
use Yoosuf\LaravelDataFlow\DataTransferObjects\QuerySpecification;
use Yoosuf\LaravelDataFlow\Enums\RunStatus;
use Yoosuf\LaravelDataFlow\Events\ImportCompleted;
use Yoosuf\LaravelDataFlow\Events\ImportFailed;
use Yoosuf\LaravelDataFlow\Events\ImportStarted;
use Yoosuf\LaravelDataFlow\Importing\ImportRunner;

final class RunImportJob implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $runId,
        public readonly ImportPlan $plan,
        public readonly ?QuerySpecification $querySpecification = null,
    )
    {
    }

    public function handle(ImportRunner $runner, ProgressStoreContract $progress): void
    {
        Event::dispatch(new ImportStarted($this->runId, $this->plan));
        $progress->put(new ProgressSnapshot(
            runId: $this->runId,
            status: RunStatus::Running,
            processedRows: 0,
            failedRows: 0,
        ));

        try {
            $result = $this->querySpecification !== null
                ? $runner->runUsingQuery($this->plan, $this->querySpecification->toBuilder())
                : $runner->run($this->plan);

            $progress->put(new ProgressSnapshot(
                runId: $this->runId,
                status: RunStatus::Completed,
                processedRows: (int) ($result['processed'] ?? 0),
                failedRows: (int) ($result['failed'] ?? 0),
            ));

            Event::dispatch(new ImportCompleted($this->runId, $this->plan));
        } catch (Throwable $throwable) {
            $progress->put(new ProgressSnapshot(
                runId: $this->runId,
                status: RunStatus::Failed,
                processedRows: 0,
                failedRows: 1,
            ));

            Event::dispatch(new ImportFailed($this->runId, $this->plan, $throwable));

            throw $throwable;
        }
    }
}
