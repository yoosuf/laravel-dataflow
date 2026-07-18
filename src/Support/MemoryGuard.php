<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Support;

use RuntimeException;

final class MemoryGuard
{
    private int $rowsSeen = 0;

    public function __construct(
        private readonly int $limitBytes,
        private readonly int $checkInterval,
        private readonly string $context,
    ) {
    }

    public static function forExport(): self
    {
        return new self(
            limitBytes: max(1, (int) config('dataflow.exports.memory_limit_bytes', 268435456)),
            checkInterval: max(1, (int) config('dataflow.exports.memory_check_interval', 500)),
            context: 'Export',
        );
    }

    public static function forImport(): self
    {
        return new self(
            limitBytes: max(1, (int) config('dataflow.imports.memory_limit_bytes', 268435456)),
            checkInterval: max(1, (int) config('dataflow.imports.memory_check_interval', 500)),
            context: 'Import',
        );
    }

    public function tick(): void
    {
        $this->rowsSeen++;

        if ($this->rowsSeen % $this->checkInterval !== 0) {
            return;
        }

        $usage = memory_get_usage(true);

        if ($usage <= $this->limitBytes) {
            return;
        }

        throw new RuntimeException(sprintf(
            '%s memory limit exceeded at row %d: %d bytes used (limit: %d bytes).',
            $this->context,
            $this->rowsSeen,
            $usage,
            $this->limitBytes,
        ));
    }
}
