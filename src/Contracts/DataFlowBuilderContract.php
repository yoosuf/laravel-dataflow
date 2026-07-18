<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Contracts;

use Illuminate\Http\Request;
use Yoosuf\LaravelDataFlow\DataTransferObjects\ImportSource;
use Yoosuf\LaravelDataFlow\Enums\ExportFormat;

interface DataFlowBuilderContract
{
    /**
     * @param array<class-string<FilterContract>> $filters
     */
    public function filters(array $filters): static;

    /**
     * @param Request|array<string, mixed> $request
     */
    public function fromRequest(Request|array $request): static;

    public function search(?string $term): static;

    /**
     * @param string|array<string>|null $sort
     */
    public function sort(string|array|null $sort): static;

    public function export(ExportFormat|string $format): ExportOperationContract;

    public function import(ImportSource $source, ImportMapContract $map): ImportOperationContract;
}
