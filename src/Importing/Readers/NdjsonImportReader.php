<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Importing\Readers;

use Illuminate\Support\Facades\Storage;
use Yoosuf\LaravelDataFlow\Contracts\ImportReaderContract;
use Yoosuf\LaravelDataFlow\DataTransferObjects\ImportSource;
use Yoosuf\LaravelDataFlow\Enums\ImportFormat;

final class NdjsonImportReader implements ImportReaderContract
{
    public function format(): ImportFormat
    {
        return ImportFormat::Ndjson;
    }

    public function rows(ImportSource $source): iterable
    {
        $stream = Storage::disk($source->disk)->readStream($source->path);

        if (! is_resource($stream)) {
            throw new \RuntimeException('Unable to open NDJSON source stream.');
        }

        try {
            while (($line = fgets($stream)) !== false) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }

                $decoded = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
                if (is_array($decoded)) {
                    yield $decoded;
                }
            }
        } finally {
            fclose($stream);
        }
    }
}
