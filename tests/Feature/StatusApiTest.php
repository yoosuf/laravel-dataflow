<?php

declare(strict_types=1);

use Yoosuf\LaravelDataFlow\Contracts\ProgressStoreContract;
use Yoosuf\LaravelDataFlow\DataTransferObjects\ProgressSnapshot;
use Yoosuf\LaravelDataFlow\Enums\RunStatus;

it('returns progress snapshot from status api', function (): void {
    app(ProgressStoreContract::class)->put(new ProgressSnapshot(
        runId: 'run-1234',
        status: RunStatus::Running,
        processedRows: 120,
        failedRows: 3,
        totalRows: 500,
        etaSeconds: 42,
    ));

    $response = $this->getJson('/dataflow/status/run-1234');

    $response->assertOk();
    $response->assertJsonPath('status', 'running');
    $response->assertJsonPath('processed_rows', 120);
    $response->assertJsonPath('failed_rows', 3);
});
