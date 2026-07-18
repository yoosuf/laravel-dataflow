<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Exporting\Exporters;

use JsonException;
use Yoosuf\LaravelDataFlow\Enums\ExportFormat;

final class JsonExporter extends AbstractStreamExporter
{
    private bool $first = true;

    public function format(): ExportFormat
    {
        return ExportFormat::Json;
    }

    /**
     * @param resource $handle
     */
    protected function onOpen($handle): void
    {
        fwrite($handle, "[");
    }

    /**
     * @param resource $handle
     * @param array<string, mixed> $row
     * @throws JsonException
     */
    protected function onWriteRow($handle, array $row): void
    {
        if (! $this->first) {
            fwrite($handle, ",");
        }

        fwrite($handle, json_encode($row, JSON_THROW_ON_ERROR));
        $this->first = false;
    }

    /**
     * @param resource $handle
     */
    protected function onClose($handle): void
    {
        fwrite($handle, "]");
    }
}
