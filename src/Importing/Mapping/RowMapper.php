<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Importing\Mapping;

use Yoosuf\LaravelDataFlow\DataTransferObjects\ColumnMap;

final class RowMapper
{
    /**
     * @param array<ColumnMap> $maps
     * @param array<string, mixed> $row
     * @return array{values: array<string, mixed>, errors: array<string>}
     */
    public function map(array $maps, array $row): array
    {
        $mapped = [];
        $errors = [];

        foreach ($maps as $map) {
            $rawValue = $this->extract($row, $map);

            if ($rawValue === null && $map->defaultValue !== null) {
                $rawValue = $map->defaultValue;
            }

            if ($map->required && ($rawValue === null || $rawValue === '')) {
                $errors[] = sprintf('Required column [%s] is missing.', $map->target);
                continue;
            }

            $mapped[$map->target] = $this->transform($rawValue, $row, $map);
        }

        return [
            'values' => $mapped,
            'errors' => $errors,
        ];
    }

    /**
     * @param array<string, mixed> $row
     */
    private function extract(array $row, ColumnMap $map): mixed
    {
        if (is_int($map->source)) {
            $values = array_values($row);

            return $values[$map->source] ?? null;
        }

        return $row[$map->source] ?? null;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function transform(mixed $value, array $row, ColumnMap $map): mixed
    {
        $current = $value;

        foreach ($map->transformers as $transformerClass) {
            if (! is_string($transformerClass) || $transformerClass === '') {
                continue;
            }

            $transformer = app($transformerClass);
            $current = $transformer($current, $row);
        }

        return $current;
    }
}
