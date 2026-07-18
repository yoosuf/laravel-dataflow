<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Exporting\Exporters;

use Yoosuf\LaravelDataFlow\Enums\ExportFormat;

final class CsvExporter extends AbstractStreamExporter
{
    private bool $headerWritten = false;

    public function format(): ExportFormat
    {
        return ExportFormat::Csv;
    }

    /**
     * @param resource $handle
     * @param array<string, mixed> $row
     */
    protected function onWriteRow($handle, array $row): void
    {
        if (! $this->headerWritten) {
            fputcsv($handle, array_keys($row));
            $this->headerWritten = true;
        }

        $values = array_map(static function (mixed $value): string {
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
        }, array_values($row));

        fputcsv($handle, $values);
    }
}
