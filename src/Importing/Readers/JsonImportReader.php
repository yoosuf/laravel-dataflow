<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Importing\Readers;

use Illuminate\Support\Facades\Storage;
use Yoosuf\LaravelDataFlow\Contracts\ImportReaderContract;
use Yoosuf\LaravelDataFlow\DataTransferObjects\ImportSource;
use Yoosuf\LaravelDataFlow\Enums\ImportFormat;

final class JsonImportReader implements ImportReaderContract
{
    public function format(): ImportFormat
    {
        return ImportFormat::Json;
    }

    public function rows(ImportSource $source): iterable
    {
        $content = Storage::disk($source->disk)->get($source->path);
        $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($decoded)) {
            return;
        }

        foreach ($decoded as $row) {
            if (is_array($row)) {
                yield $row;
            }
        }
    }
}
