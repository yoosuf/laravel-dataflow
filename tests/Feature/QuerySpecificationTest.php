<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Schema;
use Yoosuf\LaravelDataFlow\DataFlow;
use Yoosuf\LaravelDataFlow\DataTransferObjects\QuerySpecification;
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

    DataFlowUser::query()->insert([
        [
            'id' => 1,
            'tenant_id' => 1,
            'name' => 'Alice',
            'status' => 'active',
            'profile' => json_encode(['role' => 'admin']),
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'id' => 2,
            'tenant_id' => 1,
            'name' => 'Alicia',
            'status' => 'active',
            'profile' => json_encode(['role' => 'editor']),
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'id' => 3,
            'tenant_id' => 1,
            'name' => 'Bob',
            'status' => 'inactive',
            'profile' => json_encode(['role' => 'member']),
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'id' => 4,
            'tenant_id' => 2,
            'name' => 'Charlie',
            'status' => 'active',
            'profile' => json_encode(['role' => 'member']),
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    config()->set('dataflow.search.columns', ['name']);
    config()->set('dataflow.sorting.allowlist', ['name' => 'name']);
});

it('reconstructs equivalent builder constraints from query specification', function (): void {
    $query = DataFlowUser::query()
        ->where('tenant_id', 1)
        ->where('status', 'active')
        ->where(function ($builder): void {
            $builder->where('name', 'like', 'Ali%')->orWhere('name', 'Bob');
        });

    $specification = QuerySpecification::fromBuilder($query);

    $expectedIds = (clone $query)->pluck('id')->all();
    $reconstructedIds = $specification->toBuilder()->pluck('id')->all();

    sort($expectedIds);
    sort($reconstructedIds);

    expect($reconstructedIds)->toBe($expectedIds);
    expect($reconstructedIds)->toBe([1, 2]);
});

it('keeps query specification on queued distributed export chunk jobs', function (): void {
    Bus::fake();

    config()->set('dataflow.exports.distributed_threshold', 1);
    config()->set('dataflow.chunking.min_size', 100);
    config()->set('dataflow.chunking.max_size', 100);
    config()->set('dataflow.chunking.memory_limit_bytes', 4096);
    config()->set('dataflow.chunking.estimated_row_width_bytes', 256);
    config()->set('dataflow.chunking.worker_count', 1);

    DataFlow::forQuery(DataFlowUser::query()->where('tenant_id', 1)->where('status', 'active'))
        ->export('csv')
        ->to('local', 'exports/distributed-complex.csv')
        ->queue();

    Bus::assertDispatched(RunExportChunkJob::class, function (RunExportChunkJob $job): bool {
        return $job->plan->querySpecification !== null;
    });

    Bus::assertChained([
        RunExportChunkJob::class,
        MergeExportChunksJob::class,
    ]);
});
