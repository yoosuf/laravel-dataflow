<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Importing;

use Yoosuf\LaravelDataFlow\Contracts\ImportReaderContract;
use Yoosuf\LaravelDataFlow\DataTransferObjects\ImportSource;

final class ImportReaderFactory
{
    /**
     * @param array<string, class-string<ImportReaderContract>> $readers
     */
    public function __construct(private readonly array $readers)
    {
    }

    public function make(ImportSource $source): ImportReaderContract
    {
        $readerClass = $this->readers[$source->format->value] ?? null;

        if ($readerClass === null) {
            throw new \InvalidArgumentException('No import reader registered for format '.$source->format->value);
        }

        return app($readerClass);
    }
}
