<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

final class MakeDataFlowExporterCommand extends Command
{
    protected $signature = 'dataflow:exporter {name : Exporter class name}';

    protected $description = 'Generate a DataFlow exporter scaffold class.';

    public function handle(): int
    {
        $name = trim((string) $this->argument('name'));

        if ($name === '') {
            $this->error('Exporter name is required.');

            return self::FAILURE;
        }

        $basePath = app_path('DataFlow/Exporters');
        $path = $basePath.'/'.$name.'.php';

        if (File::exists($path)) {
            $this->error('Exporter class already exists at '.$path);

            return self::FAILURE;
        }

        File::ensureDirectoryExists($basePath);

        File::put($path, $this->stub($name));

        $this->info('Created exporter: '.$path);

        return self::SUCCESS;
    }

    private function stub(string $name): string
    {
        return "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\DataFlow\\Exporters;\n\nuse Yoosuf\\LaravelDataFlow\\Contracts\\ExporterContract;\nuse Yoosuf\\LaravelDataFlow\\DataTransferObjects\\ExportTarget;\nuse Yoosuf\\LaravelDataFlow\\Enums\\ExportFormat;\n\nfinal class {$name} implements ExporterContract\n{\n    public function format(): ExportFormat\n    {\n        return ExportFormat::Csv;\n    }\n\n    public function open(ExportTarget $target): void\n    {\n    }\n\n    public function writeRow(array $row): void\n    {\n    }\n\n    public function close(): void\n    {\n    }\n}\n";
    }
}
