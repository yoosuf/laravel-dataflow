<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Importing\Readers;

use RuntimeException;
use Illuminate\Support\Facades\Storage;
use Yoosuf\LaravelDataFlow\Contracts\ImportReaderContract;
use Yoosuf\LaravelDataFlow\DataTransferObjects\ImportSource;
use Yoosuf\LaravelDataFlow\Enums\ImportFormat;

final class XlsxImportReader implements ImportReaderContract
{
    public function format(): ImportFormat
    {
        return ImportFormat::Xlsx;
    }

    public function rows(ImportSource $source): iterable
    {
        if (! class_exists(\OpenSpout\Reader\XLSX\Reader::class)) {
            throw new RuntimeException('XLSX import requires openspout/openspout.');
        }

        $stream = Storage::disk($source->disk)->readStream($source->path);
        if (! is_resource($stream)) {
            throw new RuntimeException('Unable to open XLSX source stream.');
        }

        $tmpPath = tempnam(sys_get_temp_dir(), 'dataflow-xlsx-import-');

        if ($tmpPath === false) {
            fclose($stream);
            throw new RuntimeException('Unable to allocate temp XLSX path.');
        }

        $tmpHandle = fopen($tmpPath, 'wb');

        if (! is_resource($tmpHandle)) {
            fclose($stream);
            throw new RuntimeException('Unable to open temp XLSX output stream.');
        }

        stream_copy_to_stream($stream, $tmpHandle);
        fclose($stream);
        fclose($tmpHandle);

        $readerClass = \OpenSpout\Reader\XLSX\Reader::class;
        $reader = new $readerClass();
        $reader->open($tmpPath);

        $headers = null;

        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $values = $row->toArray();

                    if ($headers === null) {
                        $headers = array_map(static fn ($value): string => (string) $value, $values);
                        continue;
                    }

                    if ($headers === []) {
                        continue;
                    }

                    yield array_combine($headers, $values) ?: [];
                }
            }
        } finally {
            $reader->close();
            @unlink($tmpPath);
        }
    }
}
