<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Monitoring;

use Illuminate\Support\Facades\Cache;
use Yoosuf\LaravelDataFlow\Contracts\ProgressStoreContract;
use Yoosuf\LaravelDataFlow\DataTransferObjects\ProgressSnapshot;
use Yoosuf\LaravelDataFlow\Enums\RunStatus;

final class CacheProgressStore implements ProgressStoreContract
{
    public function put(ProgressSnapshot $snapshot): void
    {
        Cache::put($this->key($snapshot->runId), [
            'run_id' => $snapshot->runId,
            'status' => $snapshot->status->value,
            'processed_rows' => $snapshot->processedRows,
            'failed_rows' => $snapshot->failedRows,
            'total_rows' => $snapshot->totalRows,
            'eta_seconds' => $snapshot->etaSeconds,
        ], now()->addHours(24));
    }

    public function get(string $runId): ?ProgressSnapshot
    {
        $payload = Cache::get($this->key($runId));

        if (! is_array($payload)) {
            return null;
        }

        return new ProgressSnapshot(
            runId: (string) ($payload['run_id'] ?? $runId),
            status: RunStatus::from((string) ($payload['status'] ?? RunStatus::Pending->value)),
            processedRows: (int) ($payload['processed_rows'] ?? 0),
            failedRows: (int) ($payload['failed_rows'] ?? 0),
            totalRows: isset($payload['total_rows']) ? (int) $payload['total_rows'] : null,
            etaSeconds: isset($payload['eta_seconds']) ? (int) $payload['eta_seconds'] : null,
        );
    }

    private function key(string $runId): string
    {
        return 'dataflow:progress:'.$runId;
    }
}
