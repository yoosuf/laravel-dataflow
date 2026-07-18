<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Yoosuf\LaravelDataFlow\DataTransferObjects\FilterGroup;
use Yoosuf\LaravelDataFlow\DataTransferObjects\FilterRule;
use Yoosuf\LaravelDataFlow\Enums\FilterOperator;
use Yoosuf\LaravelDataFlow\Enums\LogicalOperator;
use Yoosuf\LaravelDataFlow\Enums\SoftDeleteMode;
use Yoosuf\LaravelDataFlow\Exceptions\UnsupportedFilterException;
use Yoosuf\LaravelDataFlow\Query\QueryComposer;
use Yoosuf\LaravelDataFlow\Query\QueryComposition;
use Yoosuf\LaravelDataFlow\Tests\Fixtures\Models\DataFlowPost;
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
        $table->unsignedBigInteger('tenant_id');
        $table->string('name');
        $table->string('status');
        $table->json('profile')->nullable();
        $table->timestamp('created_at')->nullable();
        $table->timestamp('updated_at')->nullable();
        $table->softDeletes();
    });

    Schema::create('posts', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
        $table->string('title');
        $table->timestamp('created_at')->nullable();
        $table->timestamp('updated_at')->nullable();
    });

    DataFlowUser::query()->create([
        'tenant_id' => 10,
        'name' => 'Alice',
        'status' => 'active',
        'profile' => ['role' => 'admin'],
        'created_at' => '2026-01-10 00:00:00',
        'updated_at' => '2026-01-10 00:00:00',
    ]);

    DataFlowUser::query()->create([
        'tenant_id' => 10,
        'name' => 'Bob',
        'status' => 'active',
        'profile' => ['role' => 'editor'],
        'created_at' => '2026-01-11 00:00:00',
        'updated_at' => '2026-01-11 00:00:00',
    ]);

    DataFlowUser::query()->create([
        'tenant_id' => 20,
        'name' => 'Eve',
        'status' => 'active',
        'profile' => ['role' => 'admin'],
        'created_at' => '2026-01-11 00:00:00',
        'updated_at' => '2026-01-11 00:00:00',
    ]);

    DataFlowUser::query()->create([
        'tenant_id' => 10,
        'name' => 'Ghost',
        'status' => 'active',
        'profile' => ['role' => 'admin'],
        'created_at' => '2026-01-20 00:00:00',
        'updated_at' => '2026-01-20 00:00:00',
        'deleted_at' => '2026-01-21 00:00:00',
    ]);

    DataFlowPost::query()->insert([
        ['user_id' => 1, 'title' => 'vip tips', 'created_at' => now(), 'updated_at' => now()],
        ['user_id' => 1, 'title' => 'growth notes', 'created_at' => now(), 'updated_at' => now()],
        ['user_id' => 2, 'title' => 'general notes', 'created_at' => now(), 'updated_at' => now()],
    ]);
});

it('applies allowlisted nested filters including relation and scope', function (): void {
    config()->set('dataflow.filters.allowlist', [
        'status' => 'status',
        'tenant' => ['type' => 'scope', 'scope' => 'tenant'],
        'posts.title' => ['type' => 'relation', 'relation' => 'posts', 'column' => 'title'],
        'name' => 'name',
    ]);

    $group = new FilterGroup(
        LogicalOperator::And,
        rules: [
            new FilterRule('status', FilterOperator::Eq, 'active'),
            new FilterRule('tenant', FilterOperator::Eq, 10),
        ],
        groups: [
            new FilterGroup(LogicalOperator::Or, rules: [
                new FilterRule('posts.title', FilterOperator::Contains, 'vip'),
                new FilterRule('name', FilterOperator::StartsWith, 'Ali'),
            ]),
        ],
    );

    $composer = app(QueryComposer::class);

    $results = $composer
        ->compose(DataFlowUser::query(), new QueryComposition($group, SoftDeleteMode::WithoutTrashed))
        ->pluck('name')
        ->all();

    expect($results)->toBe(['Alice']);
});

it('rejects a non allowlisted filter key', function (): void {
    config()->set('dataflow.filters.allowlist', [
        'status' => 'status',
    ]);

    $group = new FilterGroup(LogicalOperator::And, [
        new FilterRule('tenant_id', FilterOperator::Eq, 10),
    ]);

    $composer = app(QueryComposer::class);

    expect(fn (): array => $composer
        ->compose(DataFlowUser::query(), new QueryComposition($group))
        ->get()
        ->all())->toThrow(UnsupportedFilterException::class);
});

it('applies json path, date range, relation count, and soft delete modes', function (): void {
    config()->set('dataflow.filters.allowlist', [
        'profile.role' => ['type' => 'json', 'column' => 'profile', 'path' => 'role'],
        'created_window' => 'created_at',
        'posts_count' => ['type' => 'relation-count', 'relation' => 'posts'],
    ]);

    $group = new FilterGroup(LogicalOperator::And, [
        new FilterRule('profile.role', FilterOperator::Eq, 'admin'),
        new FilterRule('created_window', FilterOperator::DateRange, ['2026-01-01 00:00:00', '2026-01-31 23:59:59']),
        new FilterRule('posts_count', FilterOperator::Eq, 2),
    ]);

    $composer = app(QueryComposer::class);

    $activeNames = $composer
        ->compose(DataFlowUser::query(), new QueryComposition($group, SoftDeleteMode::WithoutTrashed))
        ->pluck('name')
        ->all();

    expect($activeNames)->toBe(['Alice']);

    $trashedOnly = $composer
        ->compose(DataFlowUser::query(), new QueryComposition(
            new FilterGroup(LogicalOperator::And, [new FilterRule('profile.role', FilterOperator::Eq, 'admin')]),
            SoftDeleteMode::OnlyTrashed,
        ))
        ->pluck('name')
        ->all();

    expect($trashedOnly)->toBe(['Ghost']);
});
