<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\DataTransferObjects;

final readonly class ExportChunk
{
    public function __construct(
        public int $index,
        public int $fromId,
        public int $toId,
        public string $path,
    ) {
    }
}
