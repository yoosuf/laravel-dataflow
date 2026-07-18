<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

final class MakeDataFlowFilterCommand extends Command
{
    protected $signature = 'dataflow:filter {name : Filter class name}';

    protected $description = 'Generate a DataFlow filter scaffold class.';

    public function handle(): int
    {
        $name = trim((string) $this->argument('name'));

        if ($name === '') {
            $this->error('Filter name is required.');

            return self::FAILURE;
        }

        $basePath = app_path('DataFlow/Filters');
        $path = $basePath.'/'.$name.'.php';

        if (File::exists($path)) {
            $this->error('Filter class already exists at '.$path);

            return self::FAILURE;
        }

        File::ensureDirectoryExists($basePath);

        File::put($path, $this->stub($name));

        $this->info('Created filter: '.$path);

        return self::SUCCESS;
    }

    private function stub(string $name): string
    {
        return "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\DataFlow\\Filters;\n\nuse Illuminate\\Database\\Eloquent\\Builder;\nuse Yoosuf\\LaravelDataFlow\\Contracts\\FilterContract;\nuse Yoosuf\\LaravelDataFlow\\DataTransferObjects\\FilterGroup;\n\nfinal class {$name} implements FilterContract\n{\n    public function apply(Builder $query, FilterGroup $group): Builder\n    {\n        return $query;\n    }\n}\n";
    }
}
