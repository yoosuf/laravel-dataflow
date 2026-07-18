<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Contracts;

use Yoosuf\LaravelDataFlow\DataTransferObjects\ExportPlan;

interface ExportOperationContract
{
    public function to(string $disk, string $path): static;

    public function queue(): string;

    public function sync(): string;

    /**
     * @param class-string|callable $listener
     */
    public function onCompleted(string|callable $listener): static;

    /**
     * @param class-string|callable $listener
     */
    public function onFailed(string|callable $listener): static;

    public function plan(): ExportPlan;
}
