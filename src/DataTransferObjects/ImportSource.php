<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\DataTransferObjects;

use InvalidArgumentException;
use Yoosuf\LaravelDataFlow\Enums\ImportFormat;

final readonly class ImportSource
{
    public function __construct(
        public string $disk,
        public string $path,
        public ImportFormat $format,
    ) {
        if ($this->disk === '' || $this->path === '') {
            throw new InvalidArgumentException('Import source disk and path are required.');
        }
    }
}
