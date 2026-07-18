<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Contracts;

use Yoosuf\LaravelDataFlow\DataTransferObjects\ProgressSnapshot;

interface ProgressStoreContract
{
    public function put(ProgressSnapshot $snapshot): void;

    public function get(string $runId): ?ProgressSnapshot;
}
