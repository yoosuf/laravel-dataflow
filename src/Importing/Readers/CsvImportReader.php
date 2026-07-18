<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Importing\Readers;

use Illuminate\Support\Facades\Storage;
use Yoosuf\LaravelDataFlow\Contracts\ImportReaderContract;
use Yoosuf\LaravelDataFlow\DataTransferObjects\ImportSource;
use Yoosuf\LaravelDataFlow\Enums\ImportFormat;

final class CsvImportReader implements ImportReaderContract
{
    public function format(): ImportFormat
    {
        return ImportFormat::Csv;
    }

    public function rows(ImportSource $source): iterable
    {
        $stream = Storage::disk($source->disk)->readStream($source->path);

        if (! is_resource($stream)) {
            throw new \RuntimeException('Unable to open CSV source stream.');
        }

        try {
            $headers = null;

            while (($row = fgetcsv($stream)) !== false) {
                if ($headers === null) {
                    $headers = array_map(static fn ($header): string => (string) $header, $row);
                    continue;
                }

                if ($headers === []) {
                    continue;
                }

                yield array_combine($headers, $row) ?: [];
            }
        } finally {
            fclose($stream);
        }
    }
}
