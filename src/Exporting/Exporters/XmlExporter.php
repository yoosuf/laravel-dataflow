<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Exporting\Exporters;

use Yoosuf\LaravelDataFlow\Enums\ExportFormat;

final class XmlExporter extends AbstractStreamExporter
{
    private bool $opened = false;

    public function format(): ExportFormat
    {
        return ExportFormat::Xml;
    }

    /**
     * @param resource $handle
     */
    protected function onWriteRow($handle, array $row): void
    {
        if (! $this->opened) {
            fwrite($handle, "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<rows>\n");
            $this->opened = true;
        }

        fwrite($handle, "  <row>\n");

        foreach ($row as $key => $value) {
            $field = htmlspecialchars((string) $key, ENT_XML1 | ENT_QUOTES, 'UTF-8');
            $content = htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
            fwrite($handle, sprintf("    <%s>%s</%s>\n", $field, $content, $field));
        }

        fwrite($handle, "  </row>\n");
    }

    /**
     * @param resource $handle
     */
    protected function onClose($handle): void
    {
        if (! $this->opened) {
            fwrite($handle, "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<rows/>\n");

            return;
        }

        fwrite($handle, "</rows>\n");
    }
}
