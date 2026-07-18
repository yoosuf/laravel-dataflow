<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Exporting\Exporters;

use RuntimeException;
use Yoosuf\LaravelDataFlow\Contracts\ExporterContract;
use Illuminate\Support\Facades\Storage;
use Yoosuf\LaravelDataFlow\DataTransferObjects\ExportTarget;
use Yoosuf\LaravelDataFlow\Enums\ExportFormat;

final class PdfExporter implements ExporterContract
{
    private ?ExportTarget $target = null;
    /** @var resource|null */
    private $handle = null;

    private bool $headerWritten = false;

    public function format(): ExportFormat
    {
        return ExportFormat::Pdf;
    }

    public function open(ExportTarget $target): void
    {
        $this->target = $target;
        $this->handle = tmpfile();

        if (! is_resource($this->handle)) {
            throw new RuntimeException('Unable to create PDF HTML temporary stream.');
        }
    }

    /**
     * @param array<string, mixed> $row
     */
    public function writeRow(array $row): void
    {
        if (! is_resource($this->handle)) {
            throw new RuntimeException('PDF exporter is not open.');
        }

        if (! $this->headerWritten) {
            fwrite($this->handle, '<table border="1" cellpadding="4" cellspacing="0"><thead><tr>');
            foreach (array_keys($row) as $column) {
                fwrite($this->handle, '<th>'.htmlspecialchars((string) $column, ENT_QUOTES, 'UTF-8').'</th>');
            }
            fwrite($this->handle, '</tr></thead><tbody>');
            $this->headerWritten = true;
        }

        fwrite($this->handle, '<tr>');
        foreach ($row as $value) {
            fwrite($this->handle, '<td>'.htmlspecialchars($this->normalizeCellValue($value), ENT_QUOTES, 'UTF-8').'</td>');
        }
        fwrite($this->handle, '</tr>');
    }

    private function normalizeCellValue(mixed $value): string
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
        if (! is_resource($this->handle) || $this->target === null) {
            throw new RuntimeException('PDF exporter is not open.');
        }

        fwrite($this->handle, '</tbody></table>');

        if (! class_exists(\Dompdf\Dompdf::class)) {
            throw new RuntimeException('PDF export requires dompdf/dompdf.');
        }

        rewind($this->handle);
        $html = stream_get_contents($this->handle);

        if ($html === false) {
            throw new RuntimeException('Unable to read temporary PDF HTML content.');
        }

        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml('<html><body>'.$html.'</body></html>');
        $dompdf->setPaper('A4');
        $dompdf->render();

        Storage::disk($this->target->disk)->put($this->target->path, $dompdf->output());

        fclose($this->handle);
        $this->handle = null;
        $this->target = null;
        $this->headerWritten = false;
    }
}
