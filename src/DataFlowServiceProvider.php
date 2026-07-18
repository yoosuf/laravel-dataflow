<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow;

use Yoosuf\LaravelDataFlow\Contracts\FilterContract;
use Yoosuf\LaravelDataFlow\Contracts\DataFlowFactoryContract;
use Yoosuf\LaravelDataFlow\Contracts\ChunkSizeResolverContract;
use Yoosuf\LaravelDataFlow\Contracts\ExportCoordinatorContract;
use Yoosuf\LaravelDataFlow\Contracts\MergeStrategyContract;
use Yoosuf\LaravelDataFlow\Contracts\ParquetWriterContract;
use Yoosuf\LaravelDataFlow\Contracts\ProgressStoreContract;
use Yoosuf\LaravelDataFlow\Contracts\SearchDriverContract;
use Yoosuf\LaravelDataFlow\Console\Commands\MakeDataFlowExporterCommand;
use Yoosuf\LaravelDataFlow\Console\Commands\MakeDataFlowFilterCommand;
use Yoosuf\LaravelDataFlow\Exporting\ExporterFactory;
use Yoosuf\LaravelDataFlow\Exporting\ExportChunkRunner;
use Yoosuf\LaravelDataFlow\Exporting\Chunking\AdaptiveChunkSizeResolver;
use Yoosuf\LaravelDataFlow\Exporting\Coordinator\DistributedExportCoordinator;
use Yoosuf\LaravelDataFlow\Exporting\Merging\FormatAwareMergeStrategy;
use Yoosuf\LaravelDataFlow\Exporting\ExportRunner;
use Yoosuf\LaravelDataFlow\Exporting\Parquet\StrictParquetWriter;
use Yoosuf\LaravelDataFlow\Importing\ImportReaderFactory;
use Yoosuf\LaravelDataFlow\Importing\ImportRunner;
use Yoosuf\LaravelDataFlow\Importing\Mapping\MappingPreviewService;
use Yoosuf\LaravelDataFlow\Importing\Mapping\RowMapper;
use Yoosuf\LaravelDataFlow\Filtering\AllowlistFilterEngine;
use Yoosuf\LaravelDataFlow\Filtering\FilterAllowlist;
use Yoosuf\LaravelDataFlow\Query\QueryComposer;
use Yoosuf\LaravelDataFlow\Query\Support\JsonPathExpressionFactory;
use Yoosuf\LaravelDataFlow\Search\DatabaseLikeSearchDriver;
use Yoosuf\LaravelDataFlow\Search\SearchQueryFactory;
use Yoosuf\LaravelDataFlow\Monitoring\CacheProgressStore;
use Yoosuf\LaravelDataFlow\Support\Factories\DataFlowFactory;
use Yoosuf\LaravelDataFlow\Sorting\SortAllowlist;
use Yoosuf\LaravelDataFlow\Sorting\SortingEngine;
use Illuminate\Support\ServiceProvider;

final class DataFlowServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/dataflow.php', 'dataflow');

        $this->app->singleton(FilterAllowlist::class, function (): FilterAllowlist {
            /** @var array<string, string|array<string, string>> $config */
            $config = (array) config('dataflow.filters.allowlist', []);

            return FilterAllowlist::fromConfig($config);
        });

        $this->app->singleton(JsonPathExpressionFactory::class, JsonPathExpressionFactory::class);

        $this->app->singleton(FilterContract::class, function (): FilterContract {
            return new AllowlistFilterEngine(
                $this->app->make(FilterAllowlist::class),
                $this->app->make(JsonPathExpressionFactory::class),
            );
        });

        $this->app->singleton(SearchDriverContract::class, function (): SearchDriverContract {
            /** @var array<string> $columns */
            $columns = (array) config('dataflow.search.columns', []);

            /** @var array<string, array<string>> $relations */
            $relations = (array) config('dataflow.search.relations', []);

            return new DatabaseLikeSearchDriver($columns, $relations);
        });

        $this->app->singleton(SortAllowlist::class, function (): SortAllowlist {
            /** @var array<string, string|array<string, string>> $config */
            $config = (array) config('dataflow.sorting.allowlist', []);

            return SortAllowlist::fromConfig($config);
        });

        $this->app->singleton(SortingEngine::class, function (): SortingEngine {
            return new SortingEngine($this->app->make(SortAllowlist::class));
        });

        $this->app->singleton(QueryComposer::class, function (): QueryComposer {
            return new QueryComposer(
                $this->app->make(FilterContract::class),
                $this->app->make(SearchDriverContract::class),
                $this->app->make(SortingEngine::class),
            );
        });

        $this->app->singleton(SearchQueryFactory::class, SearchQueryFactory::class);

        $this->app->singleton(ExporterFactory::class, function (): ExporterFactory {
            /** @var array<string, class-string<\Yoosuf\LaravelDataFlow\Contracts\ExporterContract>> $exporters */
            $exporters = (array) config('dataflow.exports.exporters', []);

            return new ExporterFactory($exporters);
        });

        $this->app->singleton(ExportRunner::class, function (): ExportRunner {
            return new ExportRunner(
                $this->app->make(QueryComposer::class),
                $this->app->make(ExporterFactory::class),
            );
        });

        $this->app->singleton(ImportReaderFactory::class, function (): ImportReaderFactory {
            /** @var array<string, class-string<\Yoosuf\LaravelDataFlow\Contracts\ImportReaderContract>> $readers */
            $readers = (array) config('dataflow.imports.readers', []);

            return new ImportReaderFactory($readers);
        });

        $this->app->singleton(RowMapper::class, RowMapper::class);

        $this->app->singleton(ImportRunner::class, function (): ImportRunner {
            return new ImportRunner(
                $this->app->make(ImportReaderFactory::class),
                $this->app->make(RowMapper::class),
            );
        });

        $this->app->singleton(MappingPreviewService::class, function (): MappingPreviewService {
            return new MappingPreviewService($this->app->make(ImportReaderFactory::class));
        });

        $this->app->singleton(ExportChunkRunner::class, function (): ExportChunkRunner {
            return new ExportChunkRunner(
                $this->app->make(QueryComposer::class),
                $this->app->make(ExporterFactory::class),
            );
        });

        $this->app->singleton(ChunkSizeResolverContract::class, AdaptiveChunkSizeResolver::class);
        $this->app->singleton(MergeStrategyContract::class, FormatAwareMergeStrategy::class);
        $this->app->singleton(ParquetWriterContract::class, StrictParquetWriter::class);
        $this->app->singleton(ProgressStoreContract::class, CacheProgressStore::class);
        $this->app->singleton(ExportCoordinatorContract::class, DistributedExportCoordinator::class);

        $this->app->singleton(DataFlowFactoryContract::class, DataFlowFactory::class);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/dataflow.php' => config_path('dataflow.php'),
        ], 'dataflow-config');

        if ((bool) config('dataflow.monitoring.enabled', true)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/dataflow.php');
        }

        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeDataFlowFilterCommand::class,
                MakeDataFlowExporterCommand::class,
            ]);
        }
    }
}
