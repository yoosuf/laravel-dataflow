<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Yoosuf\LaravelDataFlow\DataFlow;
use Yoosuf\LaravelDataFlow\Jobs\RunExportJob;
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
            'tenant_id' => 1,
            'name' => 'Alice',
            'status' => 'active',
            'profile' => json_encode(['role' => 'admin']),
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'tenant_id' => 1,
            'name' => 'Alicia',
            'status' => 'active',
            'profile' => json_encode(['role' => 'editor']),
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'tenant_id' => 1,
            'name' => 'Bob',
            'status' => 'active',
            'profile' => json_encode(['role' => 'editor']),
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    config()->set('dataflow.search.columns', ['name']);
    config()->set('dataflow.sorting.allowlist', ['name' => 'name']);

    Storage::fake('exports');
});

it('exports csv in sync mode via fluent api', function (): void {
    $runId = DataFlow::for(DataFlowUser::class)
        ->search('Ali')
        ->sort('name')
        ->export('csv')
        ->to('exports', 'users.csv')
        ->sync();

    expect($runId)->not->toBeEmpty();

    Storage::disk('exports')->assertExists('users.csv');

    $content = Storage::disk('exports')->get('users.csv');

    expect($content)->toContain('name');
    expect($content)->toContain('Alice');
    expect($content)->toContain('Alicia');
    expect($content)->not->toContain('Bob');
});

it('exports json and ndjson in sync mode', function (): void {
    DataFlow::for(DataFlowUser::class)
        ->search('Ali')
        ->export('json')
        ->to('exports', 'users.json')
        ->sync();

    DataFlow::for(DataFlowUser::class)
        ->search('Ali')
        ->export('ndjson')
        ->to('exports', 'users.ndjson')
        ->sync();

    Storage::disk('exports')->assertExists('users.json');
    Storage::disk('exports')->assertExists('users.ndjson');

    $json = Storage::disk('exports')->get('users.json');
    $ndjson = Storage::disk('exports')->get('users.ndjson');

    expect($json)->toContain('Alice');
    expect($json)->toContain('Alicia');
    expect($ndjson)->toContain('Alice');
    expect($ndjson)->toContain("\n");
});

it('dispatches queued export job', function (): void {
    Bus::fake();

    DataFlow::for(DataFlowUser::class)
        ->search('Ali')
        ->export('csv')
        ->to('exports', 'queued-users.csv')
        ->queue();

    Bus::assertDispatched(RunExportJob::class);
});

it('exports from a complex prebuilt query in sync mode', function (): void {
    config()->set('dataflow.search.columns', ['name']);

    $baseQuery = DataFlowUser::query()
        ->where('status', 'active')
        ->where(static fn ($query) => $query->where('name', 'Alice')->orWhere('name', 'Alicia'));

    $runId = DataFlow::forQuery($baseQuery)
        ->sort('name')
        ->export('csv')
        ->to('exports', 'complex-query-users.csv')
        ->sync();

    expect($runId)->not->toBeEmpty();

    Storage::disk('exports')->assertExists('complex-query-users.csv');

    $content = Storage::disk('exports')->get('complex-query-users.csv');

    expect($content)->toContain('Alice');
    expect($content)->toContain('Alicia');
    expect($content)->not->toContain('Bob');
});

it('queues export when using a complex query source by serializing query specification', function (): void {
    Bus::fake();

    $baseQuery = DataFlowUser::query()->where('status', 'active');

    DataFlow::forQuery($baseQuery)
        ->export('csv')
        ->to('exports', 'queued-complex-query.csv')
        ->queue();

    Bus::assertDispatched(RunExportJob::class, function (RunExportJob $job): bool {
        return $job->plan->querySpecification !== null;
    });
});

it('exports xlsx with real writer integration when dependency is installed', function (): void {
    if (! class_exists(\OpenSpout\Writer\XLSX\Writer::class)) {
        $this->markTestSkipped('openspout/openspout is not installed.');
    }

    if (! class_exists(\ZipArchive::class)) {
        $this->markTestSkipped('ext-zip is not installed.');
    }

    DataFlow::for(DataFlowUser::class)
        ->search('Ali')
        ->export('xlsx')
        ->to('exports', 'users.xlsx')
        ->sync();

    Storage::disk('exports')->assertExists('users.xlsx');

    $content = Storage::disk('exports')->get('users.xlsx');

    // XLSX files are ZIP containers and should start with the PK signature.
    expect($content)->toStartWith('PK');
});

it('exports pdf with real dompdf integration when dependency is installed', function (): void {
    if (! class_exists(\Dompdf\Dompdf::class)) {
        $this->markTestSkipped('dompdf/dompdf is not installed.');
    }

    DataFlow::for(DataFlowUser::class)
        ->search('Ali')
        ->export('pdf')
        ->to('exports', 'users.pdf')
        ->sync();

    Storage::disk('exports')->assertExists('users.pdf');

    $content = Storage::disk('exports')->get('users.pdf');

    expect($content)->toStartWith('%PDF-');
});

it('enforces strict parquet writer integration and never emits jsonl fallback artifacts', function (): void {
    expect(function (): void {
        DataFlow::for(DataFlowUser::class)
            ->search('Ali')
            ->export('parquet')
            ->to('exports', 'users.parquet')
            ->sync();
    })->toThrow(\RuntimeException::class);

    Storage::disk('exports')->assertMissing('users.parquet.jsonl');
    Storage::disk('exports')->assertMissing('users.parquet');
});

it('fails fast when export memory usage crosses configured budget', function (): void {
    config()->set('dataflow.exports.memory_limit_bytes', 1);
    config()->set('dataflow.exports.memory_check_interval', 1);

    expect(function (): void {
        DataFlow::for(DataFlowUser::class)
            ->export('csv')
            ->to('exports', 'memory-guard.csv')
            ->sync();
    })->toThrow(\RuntimeException::class, 'Export memory limit exceeded');
});
