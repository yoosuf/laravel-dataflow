<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Importing;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Yoosuf\LaravelDataFlow\DataTransferObjects\ImportPlan;
use Yoosuf\LaravelDataFlow\Importing\Mapping\RowMapper;
use Yoosuf\LaravelDataFlow\Support\MemoryGuard;

final class ImportRunner
{
    public function __construct(
        private readonly ImportReaderFactory $readers,
        private readonly RowMapper $mapper,
    ) {
    }

    /**
     * @return array{processed: int, failed: int, error_path: string|null}
     */
    public function run(ImportPlan $plan): array
    {
        /** @var Model $model */
        $model = new $plan->modelClass();

        return $this->runUsingQuery($plan, $model->newQuery());
    }

    /**
     * @return array{processed: int, failed: int, error_path: string|null}
     */
    public function runUsingQuery(ImportPlan $plan, Builder $writeQuery): array
    {
        $reader = $this->readers->make($plan->source);

        $processed = 0;
        $failed = 0;
        $chunk = [];
        $memoryGuard = MemoryGuard::forImport();

        $errorStream = null;
        $hasErrorRows = false;

        $this->assertSupportedWriteQuery($writeQuery);

        foreach ($reader->rows($plan->source) as $rowIndex => $row) {
            $memoryGuard->tick();
            $mapped = $this->mapper->map($plan->columns, $row);

            if ($mapped['errors'] !== []) {
                $failed++;
                $errorStream ??= $this->openErrorStream();
                $hasErrorRows = $this->writeErrorRow($errorStream, $hasErrorRows, [
                    'row' => $rowIndex + 1,
                    'errors' => $mapped['errors'],
                    'payload' => $row,
                ]);
                continue;
            }

            $chunk[] = $mapped['values'];

            if (count($chunk) >= $plan->chunkSize) {
                $this->persist($writeQuery, $chunk, $plan);
                $processed += count($chunk);
                $chunk = [];
            }
        }

        if ($chunk !== []) {
            $this->persist($writeQuery, $chunk, $plan);
            $processed += count($chunk);
        }

        $errorPath = null;

        if ($errorStream !== null) {
            $errorPath = $plan->errorPath;
            $this->flushErrorStream($plan, $errorStream);
        }

        return [
            'processed' => $processed,
            'failed' => $failed,
            'error_path' => $errorPath,
        ];
    }

    /**
     * @param array<array<string, mixed>> $rows
     */
    private function persist(Builder $writeQuery, array $rows, ImportPlan $plan): void
    {
        if ($plan->upsert && $plan->upsertBy !== []) {
            $writeQuery->upsert($rows, $plan->upsertBy, $plan->updateColumns);

            return;
        }

        $writeQuery->insert($rows);
    }

    private function assertSupportedWriteQuery(Builder $writeQuery): void
    {
        $query = $writeQuery->getQuery();

        if (($query->joins ?? []) !== [] || ($query->unions ?? []) !== []) {
            throw new RuntimeException('Import writes support model-scoped builders only; joins/unions are not supported for import targets.');
        }
    }

    /**
     * @return resource
     */
    private function openErrorStream()
    {
        $stream = fopen('php://temp', 'w+b');

        if (! is_resource($stream)) {
            throw new RuntimeException('Unable to open temporary stream for import errors.');
        }

        fwrite($stream, '[');

        return $stream;
    }

    /**
     * @param resource $stream
     * @param array<string, mixed> $errorRow
     */
    private function writeErrorRow($stream, bool $hasRows, array $errorRow): bool
    {
        if ($hasRows) {
            fwrite($stream, ',');
        }

        fwrite($stream, json_encode($errorRow, JSON_THROW_ON_ERROR));

        return true;
    }

    /**
     * @param resource $stream
     */
    private function flushErrorStream(ImportPlan $plan, $stream): void
    {
        try {
            fwrite($stream, ']');
            rewind($stream);

            $written = Storage::disk($plan->source->disk)->writeStream($plan->errorPath, $stream);

            if ($written === false) {
                throw new RuntimeException('Unable to write streamed import error report.');
            }
        } finally {
            fclose($stream);
        }
    }
}
