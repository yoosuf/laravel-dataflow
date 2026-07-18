<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Exporting;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use RuntimeException;
use Yoosuf\LaravelDataFlow\Contracts\ImportOperationContract;
use Yoosuf\LaravelDataFlow\Contracts\ProgressStoreContract;
use Yoosuf\LaravelDataFlow\DataTransferObjects\ImportPlan;
use Yoosuf\LaravelDataFlow\DataTransferObjects\ProgressSnapshot;
use Yoosuf\LaravelDataFlow\DataTransferObjects\QuerySpecification;
use Yoosuf\LaravelDataFlow\Enums\RunStatus;
use Yoosuf\LaravelDataFlow\Importing\ImportRunner;
use Yoosuf\LaravelDataFlow\Jobs\RunImportJob;
use Yoosuf\LaravelDataFlow\Events\ImportCompleted;
use Yoosuf\LaravelDataFlow\Events\ImportFailed;
use Yoosuf\LaravelDataFlow\Events\ImportStarted;

final class ImportOperation implements ImportOperationContract
{
    /** @var array<int, string|callable> */
    private array $completedListeners = [];

    /** @var array<int, string|callable> */
    private array $failedListeners = [];

    public function __construct(
        private readonly ImportPlan $plan,
        private readonly ImportRunner $runner,
        private readonly ProgressStoreContract $progress,
        private readonly ?Builder $baseQuery = null,
    ) {
    }

    public function queue(): string
    {
        $runId = Str::uuid()->toString();
        $this->progress->put(new ProgressSnapshot(
            runId: $runId,
            status: RunStatus::Pending,
            processedRows: 0,
            failedRows: 0,
        ));
        $querySpecification = $this->baseQuery !== null
            ? QuerySpecification::fromBuilder($this->baseQuery)
            : null;

        $job = (new RunImportJob($runId, $this->plan, $querySpecification))
            ->onQueue((string) config('dataflow.imports.queue', 'default'));
        Bus::dispatch($job);

        return $runId;
    }

    public function sync(): string
    {
        $runId = Str::uuid()->toString();
        Event::dispatch(new ImportStarted($runId, $this->plan));

        try {
            if ($this->baseQuery !== null) {
                $this->runner->runUsingQuery($this->plan, clone $this->baseQuery);
            } else {
                $this->runner->run($this->plan);
            }

            Event::dispatch(new ImportCompleted($runId, $this->plan));

            foreach ($this->completedListeners as $listener) {
                $this->invokeListener($listener, $runId);
            }

            return $runId;
        } catch (\Throwable $throwable) {
            Event::dispatch(new ImportFailed($runId, $this->plan, $throwable));

            foreach ($this->failedListeners as $listener) {
                $this->invokeListener($listener, $runId, $throwable);
            }

            throw $throwable;
        }
    }

    public function onCompleted(string|callable $listener): static
    {
        $this->completedListeners[] = $listener;

        return $this;
    }

    public function onFailed(string|callable $listener): static
    {
        $this->failedListeners[] = $listener;

        return $this;
    }

    public function plan(): ImportPlan
    {
        return $this->plan;
    }

    private function invokeListener(string|callable $listener, string $runId, ?\Throwable $throwable = null): void
    {
        if (is_callable($listener)) {
            $listener($runId, $throwable);

            return;
        }

        app($listener)->__invoke($runId, $throwable);
    }
}
