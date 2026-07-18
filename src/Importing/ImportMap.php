<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Importing;

use Yoosuf\LaravelDataFlow\Contracts\ImportMapContract;
use Yoosuf\LaravelDataFlow\DataTransferObjects\ColumnMap;

final readonly class ImportMap implements ImportMapContract
{
    /**
     * @param array<ColumnMap> $columns
     */
    public function __construct(
        private string $name,
        private array $columns,
    ) {
    }

    public function name(): string
    {
        return $this->name;
    }

    public function columns(): array
    {
        return $this->columns;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'columns' => array_map(static fn (ColumnMap $map): array => [
                'source' => $map->source,
                'target' => $map->target,
                'required' => $map->required,
                'default' => $map->defaultValue,
                'transformers' => $map->transformers,
            ], $this->columns),
        ];
    }

    public static function fromArray(array $payload): static
    {
        $columns = array_map(static fn (array $column): ColumnMap => new ColumnMap(
            source: $column['source'] ?? '',
            target: (string) ($column['target'] ?? ''),
            required: (bool) ($column['required'] ?? false),
            defaultValue: $column['default'] ?? null,
            transformers: (array) ($column['transformers'] ?? []),
        ), (array) ($payload['columns'] ?? []));

        return new self((string) ($payload['name'] ?? 'default'), $columns);
    }
}
