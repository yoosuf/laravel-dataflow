<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Exporting\Exporters;

use RuntimeException;
use Yoosuf\LaravelDataFlow\Contracts\ExporterContract;
use Yoosuf\LaravelDataFlow\DataTransferObjects\ExportTarget;
use Yoosuf\LaravelDataFlow\Enums\ExportFormat;
use Illuminate\Support\Facades\Storage;

final class XlsxExporter implements ExporterContract
{
    private ?ExportTarget $target = null;

    private ?string $tempPath = null;
    private mixed $writer = null;
    private bool $headerWritten = false;

    public function format(): ExportFormat
    {
        return ExportFormat::Xlsx;
    }

    public function open(ExportTarget $target): void
    {
        if (! class_exists(\OpenSpout\Writer\XLSX\Writer::class)) {
            throw new RuntimeException('XLSX export requires openspout/openspout.');
        }

        $this->target = $target;

        $this->tempPath = tempnam(sys_get_temp_dir(), 'dataflow-xlsx-');

        if ($this->tempPath === false) {
            throw new RuntimeException('Unable to allocate temp file for XLSX export.');
        }

        $writerClass = \OpenSpout\Writer\XLSX\Writer::class;
        $this->writer = new $writerClass();
        $this->writer->openToFile($this->tempPath);
    }

    /**
     * @param array<string, mixed> $row
     */
    public function writeRow(array $row): void
    {
        if ($this->writer === null) {
            throw new RuntimeException('XLSX exporter is not open.');
        }

        $rowClass = \OpenSpout\Common\Entity\Row::class;
        $cellClass = \OpenSpout\Common\Entity\Cell::class;

        if (! $this->headerWritten) {
            $headerCells = array_map(static fn ($value) => $cellClass::fromValue((string) $value), array_keys($row));
            $this->writer->addRow(new $rowClass($headerCells));
            $this->headerWritten = true;
        }

        $cells = array_map(static fn ($value) => $cellClass::fromValue(self::normalizeCellValue($value)), array_values($row));
        $this->writer->addRow(new $rowClass($cells));
    }

    private static function normalizeCellValue(mixed $value): string
    {
        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_THROW_ON_ERROR);
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if ($value === null) {
            return '';
        }

        return (string) $value;
    }

    public function close(): void
    {
        if ($this->writer !== null) {
            $this->writer->close();
        }

        if ($this->tempPath !== null && $this->target !== null) {
            $stream = fopen($this->tempPath, 'rb');

            if (is_resource($stream)) {
                Storage::disk($this->target->disk)->writeStream($this->target->path, $stream);
                fclose($stream);
            }

            @unlink($this->tempPath);
        }

        $this->tempPath = null;
        $this->writer = null;
        $this->target = null;
        $this->headerWritten = false;
    }
}
