<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Exceptions;

use InvalidArgumentException;
use Yoosuf\LaravelDataFlow\Enums\ExportFormat;

final class UnsupportedExportFormatException extends InvalidArgumentException
{
    public static function forFormat(ExportFormat $format): self
    {
        return new self(sprintf('Export format [%s] is not supported by the current exporter registry.', $format->value));
    }
}
