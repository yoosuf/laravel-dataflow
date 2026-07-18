<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Exporting\Merging;

use Illuminate\Support\Facades\Storage;
use Yoosuf\LaravelDataFlow\Contracts\MergeStrategyContract;
use Yoosuf\LaravelDataFlow\DataTransferObjects\ExportTarget;
use Yoosuf\LaravelDataFlow\Enums\ExportFormat;

final class FormatAwareMergeStrategy implements MergeStrategyContract
{
    public function merge(ExportFormat $format, iterable $chunkPaths, ExportTarget $target): void
    {
        match ($format) {
            ExportFormat::Csv => $this->mergeCsv($chunkPaths, $target),
            ExportFormat::Ndjson => $this->mergeLineOriented($chunkPaths, $target),
            default => $this->mergeLineOriented($chunkPaths, $target),
        };
    }

    /**
     * @param iterable<string> $chunkPaths
     */
    private function mergeCsv(iterable $chunkPaths, ExportTarget $target): void
    {
        $disk = Storage::disk($target->disk);
        $output = tmpfile();

        if (! is_resource($output)) {
            throw new \RuntimeException('Unable to allocate output stream for CSV merge.');
        }

        $firstChunk = true;

        foreach ($chunkPaths as $path) {
            $input = $disk->readStream($path);

            if (! is_resource($input)) {
                continue;
            }

            $lineNumber = 0;
            while (($line = fgets($input)) !== false) {
                if (! $firstChunk && $lineNumber === 0) {
                    $lineNumber++;
                    continue;
                }

                fwrite($output, $line);
                $lineNumber++;
            }

            fclose($input);
            $firstChunk = false;
        }

        rewind($output);
        $disk->writeStream($target->path, $output);
        fclose($output);
    }

    /**
     * @param iterable<string> $chunkPaths
     */
    private function mergeLineOriented(iterable $chunkPaths, ExportTarget $target): void
    {
        $disk = Storage::disk($target->disk);
        $output = tmpfile();

        if (! is_resource($output)) {
            throw new \RuntimeException('Unable to allocate output stream for merge.');
        }

        foreach ($chunkPaths as $path) {
            $input = $disk->readStream($path);

            if (! is_resource($input)) {
                continue;
            }

            stream_copy_to_stream($input, $output);
            fclose($input);
        }

        rewind($output);
        $disk->writeStream($target->path, $output);
        fclose($output);
    }
}
