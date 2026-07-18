<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Contracts;

use Yoosuf\LaravelDataFlow\DataTransferObjects\ColumnMap;

interface ImportMapContract
{
    public function name(): string;

    /**
     * @return array<ColumnMap>
     */
    public function columns(): array;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): static;
}
