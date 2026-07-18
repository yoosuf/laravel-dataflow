<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Exporting\Exporters;

use RuntimeException;
use Yoosuf\LaravelDataFlow\Contracts\ExporterContract;
use Yoosuf\LaravelDataFlow\DataTransferObjects\ExportTarget;
use Illuminate\Support\Facades\Storage;

abstract class AbstractStreamExporter implements ExporterContract
{
    private ExportTarget $target;

    /** @var resource|null */
    private $handle = null;

    final public function open(ExportTarget $target): void
    {
        $this->target = $target;
        $this->handle = tmpfile();

        if (! is_resource($this->handle)) {
            throw new RuntimeException('Unable to open temporary stream for export.');
        }

        $this->onOpen($this->handle);
    }

    /**
     * @param array<string, mixed> $row
     */
    final public function writeRow(array $row): void
    {
        $handle = $this->handle();
        $this->onWriteRow($handle, $row);
    }

    final public function close(): void
    {
        $handle = $this->handle();

        $this->onClose($handle);

        rewind($handle);
        Storage::disk($this->target->disk)->writeStream($this->target->path, $handle);

        fclose($handle);
        $this->handle = null;
    }

    /**
     * @param resource $handle
     */
    protected function onOpen($handle): void
    {
    }

    /**
     * @param resource $handle
     * @param array<string, mixed> $row
     */
    abstract protected function onWriteRow($handle, array $row): void;

    /**
     * @param resource $handle
     */
    protected function onClose($handle): void
    {
    }

    /**
     * @return resource
     */
    final protected function handle()
    {
        if (! is_resource($this->handle)) {
            throw new RuntimeException('Exporter stream is not open.');
        }

        return $this->handle;
    }
}
