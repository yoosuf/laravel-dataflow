<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Importing\Mapping;

use Yoosuf\LaravelDataFlow\DataTransferObjects\ImportSource;
use Yoosuf\LaravelDataFlow\Importing\ImportReaderFactory;

final class MappingPreviewService
{
    public function __construct(private readonly ImportReaderFactory $readers)
    {
    }

    /**
     * @return array{headers: array<string>, sample: array<array<string, mixed>>}
     */
    public function preview(ImportSource $source, int $sampleSize = 5): array
    {
        $reader = $this->readers->make($source);

        $headers = [];
        $sample = [];

        foreach ($reader->rows($source) as $index => $row) {
            if ($headers === []) {
                $headers = array_keys($row);
            }

            $sample[] = $row;

            if ($index + 1 >= $sampleSize) {
                break;
            }
        }

        return [
            'headers' => $headers,
            'sample' => $sample,
        ];
    }
}
