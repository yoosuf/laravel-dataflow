<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Yoosuf\LaravelDataFlow\DataTransferObjects\SearchQuery;
use Yoosuf\LaravelDataFlow\DataTransferObjects\SortRule;
use Yoosuf\LaravelDataFlow\DataTransferObjects\WeightedColumn;
use Yoosuf\LaravelDataFlow\Enums\SortDirection;
use Yoosuf\LaravelDataFlow\Exceptions\UnsupportedSortException;
use Yoosuf\LaravelDataFlow\Query\QueryComposer;
use Yoosuf\LaravelDataFlow\Query\QueryComposition;
use Yoosuf\LaravelDataFlow\Tests\Fixtures\Models\DataFlowPost;
use Yoosuf\LaravelDataFlow\Tests\Fixtures\Models\DataFlowUser;
use Yoosuf\LaravelDataFlow\Tests\Fixtures\SortStrategies\NameLengthSortStrategy;

beforeEach(function (): void {
    config()->set('database.default', 'testing');
    config()->set('database.connections.testing', [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
        'foreign_key_constraints' => true,
    ]);

    Schema::dropAllTables();

    Schema::create('companies', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
    });

    Schema::create('users', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('tenant_id')->default(1);
        $table->unsignedBigInteger('company_id')->nullable();
        $table->string('name');
        $table->string('status');
        $table->json('profile')->nullable();
        $table->timestamp('created_at')->nullable();
        $table->timestamp('updated_at')->nullable();
        $table->timestamp('deleted_at')->nullable();
    });

    Schema::create('posts', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
        $table->string('title');
        $table->timestamp('created_at')->nullable();
        $table->timestamp('updated_at')->nullable();
    });

    Schema::table('users', function (Blueprint $table): void {
        $table->foreign('company_id')->references('id')->on('companies')->nullOnDelete();
    });

    db()->table('companies')->insert([
        ['id' => 1, 'name' => 'Alpha Inc'],
        ['id' => 2, 'name' => 'Beta Labs'],
    ]);

    DataFlowUser::query()->insert([
        [
            'id' => 1,
            'tenant_id' => 1,
            'company_id' => 2,
            'name' => 'Alice',
            'status' => 'active',
            'profile' => json_encode(['role' => 'admin']),
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'id' => 2,
            'tenant_id' => 1,
            'company_id' => 1,
            'name' => 'Al',
            'status' => 'active',
            'profile' => json_encode(['role' => 'editor']),
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'id' => 3,
            'tenant_id' => 1,
            'company_id' => 1,
            'name' => 'Bob',
            'status' => 'active',
            'profile' => json_encode(['role' => 'editor']),
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    DataFlowPost::query()->insert([
        ['user_id' => 1, 'title' => 'vip launch', 'created_at' => now(), 'updated_at' => now()],
        ['user_id' => 2, 'title' => 'baseline guide', 'created_at' => now(), 'updated_at' => now()],
    ]);
});

it('applies relation-aware search and weighted columns', function (): void {
    config()->set('dataflow.search.columns', ['name']);
    config()->set('dataflow.search.relations', ['posts' => ['title']]);

    $search = new SearchQuery(
        terms: ['vip'],
        columns: [
            new WeightedColumn('name', 1),
        ],
        relations: ['posts'],
    );

    $results = app(QueryComposer::class)
        ->compose(DataFlowUser::query(), new QueryComposition(search: $search))
        ->pluck('name')
        ->all();

    expect($results)->toBe(['Alice']);
});

it('sorts by allowlisted relation subquery and custom strategy', function (): void {
    config()->set('dataflow.sorting.allowlist', [
        'company.name' => [
            'type' => 'relation-subquery',
            'table' => 'companies',
            'owner_key' => 'companies.id',
            'foreign_key' => 'users.company_id',
            'column' => 'name',
        ],
        'name_length' => [
            'type' => 'custom',
            'strategy' => NameLengthSortStrategy::class,
        ],
    ]);

    $composer = app(QueryComposer::class);

    $byCompany = $composer
        ->compose(DataFlowUser::query(), new QueryComposition(sorts: [
            new SortRule('company.name', SortDirection::Asc),
        ]))
        ->pluck('name')
        ->all();

    expect($byCompany)->toBe(['Al', 'Bob', 'Alice']);

    $byLengthDesc = $composer
        ->compose(DataFlowUser::query(), new QueryComposition(sorts: [
            new SortRule('name_length', SortDirection::Desc),
        ]))
        ->pluck('name')
        ->all();

    expect($byLengthDesc)->toBe(['Alice', 'Bob', 'Al']);
});

it('rejects non allowlisted sort fields', function (): void {
    config()->set('dataflow.sorting.allowlist', [
        'name' => 'name',
    ]);

    expect(fn (): array => app(QueryComposer::class)
        ->compose(DataFlowUser::query(), new QueryComposition(sorts: [
            new SortRule('status', SortDirection::Asc),
        ]))
        ->get()
        ->all())->toThrow(UnsupportedSortException::class);
});
