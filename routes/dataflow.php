<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Yoosuf\LaravelDataFlow\Http\Controllers\MappingPreviewController;
use Yoosuf\LaravelDataFlow\Http\Controllers\StatusController;

Route::prefix((string) config('dataflow.monitoring.route_prefix', 'dataflow'))
    ->group(function (): void {
        Route::post('/mapping-preview', MappingPreviewController::class)
            ->name('dataflow.mapping-preview');

        Route::get('/status/{runId}', StatusController::class)
            ->name('dataflow.status');
    });
