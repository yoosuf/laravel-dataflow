<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Yoosuf\LaravelDataFlow\DataFlow;
use Yoosuf\LaravelDataFlow\Contracts\ProgressStoreContract;
use Yoosuf\LaravelDataFlow\DataTransferObjects\ColumnMap;
use Yoosuf\LaravelDataFlow\DataTransferObjects\ImportSource;
use Yoosuf\LaravelDataFlow\DataTransferObjects\QuerySpecification;
use Yoosuf\LaravelDataFlow\Enums\ImportFormat;
use Yoosuf\LaravelDataFlow\Enums\RunStatus;
use Yoosuf\LaravelDataFlow\Events\ImportCompleted;
use Yoosuf\LaravelDataFlow\Events\ImportFailed;
use Yoosuf\LaravelDataFlow\Events\ImportStarted;
use Yoosuf\LaravelDataFlow\Importing\ImportRunner;
use Yoosuf\LaravelDataFlow\Importing\ImportMap;
use Yoosuf\LaravelDataFlow\Jobs\RunImportJob;
use Yoosuf\LaravelDataFlow\Tests\Fixtures\Models\DataFlowUser;
use Yoosuf\LaravelDataFlow\Tests\Fixtures\Transformers\TrimTransformer;

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
        $table->string('name')->nullable();
        $table->string('status')->nullable();
        $table->string('email')->nullable();
        $table->json('profile')->nullable();
        $table->timestamp('created_at')->nullable();
        $table->timestamp('updated_at')->nullable();
        $table->timestamp('deleted_at')->nullable();
    });

    Storage::fake('imports');

    Storage::disk('imports')->put('users.csv', implode("\n", [
        'name,status,email',
        '  Alice  ,active,alice@example.com',
        ',inactive,bad@example.com',
    ]));

    config()->set('dataflow.imports.chunk_size', 2);
    config()->set('dataflow.imports.error_prefix', 'imports/errors');
});

it('imports csv with mapping, transformers, and error report output', function (): void {
    $map = new ImportMap('users-map', [
        new ColumnMap('name', 'name', required: true, transformers: [TrimTransformer::class]),
        new ColumnMap('status', 'status', required: true),
        new ColumnMap('email', 'email'),
    ]);

    $source = new ImportSource('imports', 'users.csv', ImportFormat::Csv);

    $runId = DataFlow::for(DataFlowUser::class)
        ->import($source, $map)
        ->sync();

    expect($runId)->not->toBeEmpty();

    $users = DataFlowUser::query()->orderBy('id')->get(['name', 'status', 'email'])->toArray();

    expect($users)->toHaveCount(1);
    expect($users[0]['name'])->toBe('Alice');

    $files = Storage::disk('imports')->allFiles('imports/errors');
    expect($files)->not->toBeEmpty();

    $errorJson = Storage::disk('imports')->get($files[0]);
    expect($errorJson)->toContain('Required column [name] is missing.');
});

it('stores pending progress and dispatches queued import job with run id', function (): void {
    Bus::fake();
    Event::fake();

    $map = new ImportMap('users-map', [
        new ColumnMap('name', 'name', required: true, transformers: [TrimTransformer::class]),
        new ColumnMap('status', 'status', required: true),
        new ColumnMap('email', 'email'),
    ]);

    $source = new ImportSource('imports', 'users.csv', ImportFormat::Csv);

    $runId = DataFlow::for(DataFlowUser::class)
        ->import($source, $map)
        ->queue();

    expect($runId)->not->toBeEmpty();

    $snapshot = app(ProgressStoreContract::class)->get($runId);
    expect($snapshot)->not->toBeNull();
    expect($snapshot?->status)->toBe(RunStatus::Pending);

    Event::assertNotDispatched(ImportStarted::class);
    Event::assertNotDispatched(ImportCompleted::class);
    Event::assertNotDispatched(ImportFailed::class);

    Bus::assertDispatched(RunImportJob::class, function (RunImportJob $job) use ($runId): bool {
        return $job->runId === $runId;
    });
});

it('updates progress and events when queued import job succeeds', function (): void {
    Event::fake();

    $map = new ImportMap('users-map', [
        new ColumnMap('name', 'name', required: true, transformers: [TrimTransformer::class]),
        new ColumnMap('status', 'status', required: true),
        new ColumnMap('email', 'email'),
    ]);

    $source = new ImportSource('imports', 'users.csv', ImportFormat::Csv);
    $runId = 'queued-import-success-run';

    $plan = DataFlow::for(DataFlowUser::class)
        ->import($source, $map)
        ->plan();

    $job = new RunImportJob($runId, $plan);
    $job->handle(app(ImportRunner::class), app(ProgressStoreContract::class));

    Event::assertDispatched(ImportStarted::class, function (ImportStarted $event) use ($runId): bool {
        return $event->runId === $runId;
    });

    Event::assertDispatched(ImportCompleted::class, function (ImportCompleted $event) use ($runId): bool {
        return $event->runId === $runId;
    });

    Event::assertNotDispatched(ImportFailed::class);

    $snapshot = app(ProgressStoreContract::class)->get($runId);
    expect($snapshot)->not->toBeNull();
    expect($snapshot?->status)->toBe(RunStatus::Completed);
    expect($snapshot?->processedRows)->toBe(1);
    expect($snapshot?->failedRows)->toBe(1);
});

it('updates progress and events when queued import job fails', function (): void {
    Event::fake();

    $map = new ImportMap('users-map', [
        new ColumnMap('name', 'name', required: true),
        new ColumnMap('status', 'status', required: true),
    ]);

    $missingSource = new ImportSource('imports', 'missing.csv', ImportFormat::Csv);
    $runId = 'queued-import-failed-run';

    $plan = DataFlow::for(DataFlowUser::class)
        ->import($missingSource, $map)
        ->plan();

    $job = new RunImportJob($runId, $plan);

    expect(function () use ($job): void {
        $job->handle(app(ImportRunner::class), app(ProgressStoreContract::class));
    })->toThrow(\RuntimeException::class);

    Event::assertDispatched(ImportStarted::class, function (ImportStarted $event) use ($runId): bool {
        return $event->runId === $runId;
    });

    Event::assertDispatched(ImportFailed::class, function (ImportFailed $event) use ($runId): bool {
        return $event->runId === $runId;
    });

    Event::assertNotDispatched(ImportCompleted::class);

    $snapshot = app(ProgressStoreContract::class)->get($runId);
    expect($snapshot)->not->toBeNull();
    expect($snapshot?->status)->toBe(RunStatus::Failed);
});

it('imports successfully from a model-scoped complex query source in sync mode', function (): void {
    $map = new ImportMap('users-map', [
        new ColumnMap('name', 'name', required: true, transformers: [TrimTransformer::class]),
        new ColumnMap('status', 'status', required: true),
        new ColumnMap('email', 'email'),
    ]);

    $source = new ImportSource('imports', 'users.csv', ImportFormat::Csv);

    $runId = DataFlow::forQuery(DataFlowUser::query()->where('tenant_id', 1))
        ->import($source, $map)
        ->sync();

    expect($runId)->not->toBeEmpty();
    expect(DataFlowUser::query()->count())->toBe(1);
});

it('queues import when using a complex query source by serializing query specification', function (): void {
    Bus::fake();

    $map = new ImportMap('users-map', [
        new ColumnMap('name', 'name', required: true),
        new ColumnMap('status', 'status', required: true),
    ]);

    $source = new ImportSource('imports', 'users.csv', ImportFormat::Csv);

    DataFlow::forQuery(DataFlowUser::query()->where('tenant_id', 1))
        ->import($source, $map)
        ->queue();

    Bus::assertDispatched(RunImportJob::class, function (RunImportJob $job): bool {
        return $job->querySpecification !== null;
    });
});

it('runs queued import job with a serialized query specification', function (): void {
    Event::fake();

    $map = new ImportMap('users-map', [
        new ColumnMap('name', 'name', required: true, transformers: [TrimTransformer::class]),
        new ColumnMap('status', 'status', required: true),
        new ColumnMap('email', 'email'),
    ]);

    $source = new ImportSource('imports', 'users.csv', ImportFormat::Csv);
    $runId = 'queued-import-with-query-spec';

    $plan = DataFlow::for(DataFlowUser::class)
        ->import($source, $map)
        ->plan();

    $querySpecification = QuerySpecification::fromBuilder(
        DataFlowUser::query()->where('tenant_id', 1),
    );

    $job = new RunImportJob($runId, $plan, $querySpecification);
    $job->handle(app(ImportRunner::class), app(ProgressStoreContract::class));

    Event::assertDispatched(ImportStarted::class, fn (ImportStarted $event): bool => $event->runId === $runId);
    Event::assertDispatched(ImportCompleted::class, fn (ImportCompleted $event): bool => $event->runId === $runId);
    Event::assertNotDispatched(ImportFailed::class);

    $snapshot = app(ProgressStoreContract::class)->get($runId);
    expect($snapshot)->not->toBeNull();
    expect($snapshot?->status)->toBe(RunStatus::Completed);
    expect($snapshot?->processedRows)->toBe(1);
    expect($snapshot?->failedRows)->toBe(1);
});

it('fails fast when import memory usage crosses configured budget', function (): void {
    config()->set('dataflow.imports.memory_limit_bytes', 1);
    config()->set('dataflow.imports.memory_check_interval', 1);

    $map = new ImportMap('users-map', [
        new ColumnMap('name', 'name', required: true),
        new ColumnMap('status', 'status', required: true),
        new ColumnMap('email', 'email'),
    ]);

    $source = new ImportSource('imports', 'users.csv', ImportFormat::Csv);

    expect(function () use ($source, $map): void {
        DataFlow::for(DataFlowUser::class)
            ->import($source, $map)
            ->sync();
    })->toThrow(\RuntimeException::class, 'Import memory limit exceeded');
});
