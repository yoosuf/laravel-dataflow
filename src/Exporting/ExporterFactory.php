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
     * @param array<string, string> $fallbackFormatMap
     */
    public function __construct(
        private readonly array $exporters,
        private readonly bool $fallbackEnabled = false,
        private readonly array $fallbackFormatMap = [],
        private readonly ?string $defaultFallbackFormat = null,
    )
    {
    }

    public function make(ExportFormat $format): ExporterContract
    {
        $current = $format->value;
        $attempted = [$current];

        while (true) {
            $exporterClass = $this->exporters[$current] ?? null;

            if (is_string($exporterClass) && is_a($exporterClass, ExporterContract::class, true)) {
                return app($exporterClass);
            }

            if (! $this->fallbackEnabled) {
                throw UnsupportedExportFormatException::forFormat($format);
            }

            $next = $this->fallbackFormatMap[$current] ?? $this->defaultFallbackFormat;

            if (! is_string($next) || $next === '') {
                throw UnsupportedExportFormatException::forFormat($format);
            }

            if (ExportFormat::tryFrom($next) === null) {
                throw UnsupportedExportFormatException::forFormat($format);
            }

            if (in_array($next, $attempted, true)) {
                throw UnsupportedExportFormatException::forFormat($format);
            }

            $attempted[] = $next;
            $current = $next;
        }
    }
}
