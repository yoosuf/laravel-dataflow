<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Exporting;

use Yoosuf\LaravelDataFlow\Contracts\ExporterContract;
use Yoosuf\LaravelDataFlow\Enums\ExportFormat;
use Yoosuf\LaravelDataFlow\Exceptions\UnsupportedExportFormatException;

final class ExporterFactory
{
    /**
     * @param array<string, class-string<ExporterContract>> $exporters
     */
    public function __construct(private readonly array $exporters)
    {
    }

    public function make(ExportFormat $format): ExporterContract
    {
        $exporterClass = $this->exporters[$format->value] ?? null;

        if ($exporterClass === null) {
            throw UnsupportedExportFormatException::forFormat($format);
        }

        return app($exporterClass);
    }
}
