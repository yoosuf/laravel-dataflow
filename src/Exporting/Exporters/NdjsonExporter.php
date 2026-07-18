<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Exporting\Exporters;

use JsonException;
use Yoosuf\LaravelDataFlow\Enums\ExportFormat;

final class NdjsonExporter extends AbstractStreamExporter
{
    public function format(): ExportFormat
    {
        return ExportFormat::Ndjson;
    }

    /**
     * @param resource $handle
     * @param array<string, mixed> $row
     * @throws JsonException
     */
    protected function onWriteRow($handle, array $row): void
    {
        fwrite($handle, json_encode($row, JSON_THROW_ON_ERROR)."\n");
    }
}
