<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Yoosuf\LaravelDataFlow\DataTransferObjects\ExportChunk;
use Yoosuf\LaravelDataFlow\DataTransferObjects\ExportPlan;
use Yoosuf\LaravelDataFlow\Exporting\ExportChunkRunner;

final class RunExportChunkJob implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly ExportPlan $plan,
        public readonly ExportChunk $chunk,
    ) {
    }

    public function handle(ExportChunkRunner $runner): void
    {
        $runner->run($this->plan, $this->chunk);
    }
}
