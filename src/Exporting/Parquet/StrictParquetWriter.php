<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Exporting\Parquet;

use RuntimeException;
use Yoosuf\LaravelDataFlow\Contracts\ParquetWriterContract;
use Yoosuf\LaravelDataFlow\DataTransferObjects\ExportTarget;

final class StrictParquetWriter implements ParquetWriterContract
{
    public function writeRows(iterable $rows, ExportTarget $target): void
    {
        if (! class_exists(\Codename\Parquet\ParquetWriter::class)) {
            throw new RuntimeException('Parquet export requires codename/parquet.');
        }

        throw new RuntimeException(
            'Strict parquet integration is enabled. Bind a production Parquet writer implementation to '
            .ParquetWriterContract::class.' to write true .parquet files.',
        );
    }
}
