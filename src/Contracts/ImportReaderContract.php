<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Contracts;

use Yoosuf\LaravelDataFlow\DataTransferObjects\ImportSource;
use Yoosuf\LaravelDataFlow\Enums\ImportFormat;

interface ImportReaderContract
{
    public function format(): ImportFormat;

    /**
     * @return iterable<array<string, mixed>>
     */
    public function rows(ImportSource $source): iterable;
}
