<?php

declare(strict_types=1);

it('exposes contract interfaces for extension and fluent api', function (): void {
    $interfaces = [
        Yoosuf\LaravelDataFlow\Contracts\DataFlowFactoryContract::class,
        Yoosuf\LaravelDataFlow\Contracts\DataFlowBuilderContract::class,
        Yoosuf\LaravelDataFlow\Contracts\ExportOperationContract::class,
        Yoosuf\LaravelDataFlow\Contracts\ImportOperationContract::class,
        Yoosuf\LaravelDataFlow\Contracts\FilterContract::class,
        Yoosuf\LaravelDataFlow\Contracts\SearchDriverContract::class,
        Yoosuf\LaravelDataFlow\Contracts\SortStrategyContract::class,
        Yoosuf\LaravelDataFlow\Contracts\QueryPipeContract::class,
        Yoosuf\LaravelDataFlow\Contracts\ExporterContract::class,
        Yoosuf\LaravelDataFlow\Contracts\ImportReaderContract::class,
        Yoosuf\LaravelDataFlow\Contracts\ImportMapContract::class,
        Yoosuf\LaravelDataFlow\Contracts\MergeStrategyContract::class,
        Yoosuf\LaravelDataFlow\Contracts\ChunkSizeResolverContract::class,
        Yoosuf\LaravelDataFlow\Contracts\ProgressStoreContract::class,
        Yoosuf\LaravelDataFlow\Contracts\ExportCoordinatorContract::class,
    ];

    foreach ($interfaces as $interface) {
        expect(interface_exists($interface))->toBeTrue();
    }
});
