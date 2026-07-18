<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Exporting;

use Illuminate\Database\Eloquent\Builder;
use RuntimeException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Event;
use Yoosuf\LaravelDataFlow\Contracts\ExportCoordinatorContract;
use Yoosuf\LaravelDataFlow\Contracts\ExportOperationContract;
use Yoosuf\LaravelDataFlow\DataTransferObjects\ExportPlan;
use Yoosuf\LaravelDataFlow\DataTransferObjects\ExportTarget;
use Yoosuf\LaravelDataFlow\DataTransferObjects\QuerySpecification;
use Yoosuf\LaravelDataFlow\Enums\ExportFormat;
use Yoosuf\LaravelDataFlow\Events\ExportCompleted;
use Yoosuf\LaravelDataFlow\Events\ExportFailed;
use Yoosuf\LaravelDataFlow\Events\ExportStarted;

final class ExportOperation implements ExportOperationContract
{
    private ?ExportTarget $target = null;

    /** @var array<int, string|callable> */
    private array $completedListeners = [];

    /** @var array<int, string|callable> */
    private array $failedListeners = [];

    public function __construct(
        private readonly string $modelClass,
        private readonly ?Builder $baseQuery,
        private readonly ExportFormat $format,
        private readonly ?\Yoosuf\LaravelDataFlow\DataTransferObjects\FilterGroup $filterGroup,
        private readonly ?\Yoosuf\LaravelDataFlow\DataTransferObjects\SearchQuery $search,
        /** @var array<\Yoosuf\LaravelDataFlow\DataTransferObjects\SortRule> */
        private readonly array $sorts,
        private readonly ExportRunner $runner,
    ) {
    }

    public function to(string $disk, string $path): static
    {
        $this->target = new ExportTarget($disk, $path);

        return $this;
    }

    public function queue(): string
    {
        $plan = $this->plan();
        $runId = app(ExportCoordinatorContract::class)->dispatch($plan);
        Event::dispatch(new ExportStarted($runId, $plan));

        return $runId;
    }

    public function sync(): string
    {
        $runId = Str::uuid()->toString();
        $plan = $this->plan();
        Event::dispatch(new ExportStarted($runId, $plan));

        try {
            if ($this->baseQuery !== null) {
                $this->runner->runWithQuery($plan, clone $this->baseQuery);
            } else {
                $this->runner->run($plan);
            }

            Event::dispatch(new ExportCompleted($runId, $plan));

            foreach ($this->completedListeners as $listener) {
                $this->invokeListener($listener, $runId);
            }

            return $runId;
        } catch (\Throwable $throwable) {
            Event::dispatch(new ExportFailed($runId, $plan, $throwable));

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

    public function plan(): ExportPlan
    {
        $target = $this->target ?? new ExportTarget(
            (string) config('dataflow.defaults.disk', 'local'),
            'exports/dataflow-'.Str::uuid()->toString().'.'.$this->format->value,
        );

        return new ExportPlan(
            modelClass: $this->modelClass,
            format: $this->format,
            target: $target,
            filterGroup: $this->filterGroup,
            search: $this->search,
            sorts: $this->sorts,
            querySpecification: $this->baseQuery !== null ? QuerySpecification::fromBuilder($this->baseQuery) : null,
        );
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
