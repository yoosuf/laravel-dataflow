<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\DataTransferObjects;

use InvalidArgumentException;

final readonly class ExportTarget
{
    public function __construct(
        public string $disk,
        public string $path,
    ) {
        if ($this->disk === '' || $this->path === '') {
            throw new InvalidArgumentException('Export target disk and path are required.');
        }
    }
}
