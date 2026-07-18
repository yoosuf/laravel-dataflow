<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Schema;
use Yoosuf\LaravelDataFlow\DataFlow;
use Yoosuf\LaravelDataFlow\Jobs\MergeExportChunksJob;
use Yoosuf\LaravelDataFlow\Jobs\RunExportChunkJob;
use Yoosuf\LaravelDataFlow\Tests\Fixtures\Models\DataFlowUser;

beforeEach(function (): void {
    config()->set('database.default', 'testing');
    config()->set('database.connections.testing', [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
        'foreign_key_constraints' => true,
    ]);

    Schema::dropAllTables();

    Schema::create('users', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('tenant_id')->default(1);
        $table->string('name');
        $table->string('status');
        $table->json('profile')->nullable();
        $table->timestamp('created_at')->nullable();
        $table->timestamp('updated_at')->nullable();
        $table->timestamp('deleted_at')->nullable();
    });

    for ($i = 1; $i <= 8; $i++) {
        DataFlowUser::query()->create([
            'tenant_id' => 1,
            'name' => 'User '.$i,
            'status' => 'active',
            'profile' => ['role' => 'member'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    config()->set('dataflow.search.columns', ['name']);
    config()->set('dataflow.sorting.allowlist', ['name' => 'name']);
    config()->set('dataflow.chunking.min_size', 2);
    config()->set('dataflow.chunking.max_size', 3);
    config()->set('dataflow.chunking.memory_limit_bytes', 2048);
    config()->set('dataflow.chunking.estimated_row_width_bytes', 256);
    config()->set('dataflow.chunking.worker_count', 1);
    config()->set('dataflow.exports.distributed_threshold', 1);
});

it('dispatches distributed chunk export chain when threshold is exceeded', function (): void {
    Bus::fake();

    DataFlow::for(DataFlowUser::class)
        ->search('User')
        ->export('csv')
        ->to('local', 'exports/distributed-users.csv')
        ->queue();

    Bus::assertChained(function (array $jobs): bool {
        if (count($jobs) < 2) {
            return false;
        }

        $hasChunk = false;
        $hasMerge = false;

        foreach ($jobs as $job) {
            if ($job instanceof RunExportChunkJob) {
                $hasChunk = true;
            }

            if ($job instanceof MergeExportChunksJob) {
                $hasMerge = true;
            }
        }

        return $hasChunk && $hasMerge;
    });
});
