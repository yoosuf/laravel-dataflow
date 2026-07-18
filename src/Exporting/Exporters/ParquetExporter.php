<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Exporting\Exporters;

use RuntimeException;
use Yoosuf\LaravelDataFlow\Contracts\ParquetWriterContract;
use Yoosuf\LaravelDataFlow\Contracts\ExporterContract;
use Yoosuf\LaravelDataFlow\DataTransferObjects\ExportTarget;
use Yoosuf\LaravelDataFlow\Enums\ExportFormat;

final class ParquetExporter implements ExporterContract
{
    public function __construct(private readonly ParquetWriterContract $writer)
    {
    }

    private ?ExportTarget $target = null;
    /** @var resource|null */
    private $handle = null;

    public function open(ExportTarget $target): void
    {
        $this->target = $target;
        $this->handle = tmpfile();

        if (! is_resource($this->handle)) {
            throw new RuntimeException('Unable to create temporary parquet stream.');
        }
    }

    /**
     * @param array<string, mixed> $row
     */
    public function writeRow(array $row): void
    {
        if (! is_resource($this->handle)) {
            throw new RuntimeException('Parquet exporter is not open.');
        }

        fwrite($this->handle, json_encode($row, JSON_THROW_ON_ERROR)."\n");
    }

    public function format(): ExportFormat
    {
        return ExportFormat::Parquet;
    }

    public function close(): void
    {
        if (! is_resource($this->handle) || $this->target === null) {
            throw new RuntimeException('Parquet exporter is not open.');
        }

        rewind($this->handle);
        $rows = $this->rowsFromStream($this->handle);
        $this->writer->writeRows($rows, $this->target);

        fclose($this->handle);
        $this->handle = null;
        $this->target = null;
    }

    /**
     * @param resource $stream
     * @return iterable<array<string, mixed>>
     */
    private function rowsFromStream($stream): iterable
    {
        while (($line = fgets($stream)) !== false) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $decoded = json_decode($line, true, 512, JSON_THROW_ON_ERROR);

            if (is_array($decoded)) {
                yield $decoded;
            }
        }
    }
}
