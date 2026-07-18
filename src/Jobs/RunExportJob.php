<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Yoosuf\LaravelDataFlow\DataTransferObjects\ExportPlan;
use Yoosuf\LaravelDataFlow\Exporting\ExportRunner;

final class RunExportJob implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly ExportPlan $plan)
    {
    }

    public function handle(ExportRunner $runner): void
    {
        $runner->run($this->plan);
    }
}
