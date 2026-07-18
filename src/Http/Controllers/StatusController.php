<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Yoosuf\LaravelDataFlow\Contracts\ProgressStoreContract;

final class StatusController extends Controller
{
    public function __invoke(string $runId, ProgressStoreContract $progress): JsonResponse
    {
        $snapshot = $progress->get($runId);

        if ($snapshot === null) {
            return response()->json([
                'message' => 'Run status not found.',
            ], 404);
        }

        return response()->json([
            'run_id' => $snapshot->runId,
            'status' => $snapshot->status->value,
            'processed_rows' => $snapshot->processedRows,
            'failed_rows' => $snapshot->failedRows,
            'total_rows' => $snapshot->totalRows,
            'eta_seconds' => $snapshot->etaSeconds,
        ]);
    }
}
