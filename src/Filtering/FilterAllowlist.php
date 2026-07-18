<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Filtering;

use InvalidArgumentException;

final class FilterAllowlist
{
    /**
     * @var array<string, AllowedFilter>
     */
    private array $filtersByKey;

    /**
     * @param array<AllowedFilter> $filters
     */
    public function __construct(array $filters)
    {
        $this->filtersByKey = [];

        foreach ($filters as $filter) {
            $this->filtersByKey[$filter->key] = $filter;
        }
    }

    /**
     * @param array<string, string|array<string, string>> $config
     */
    public static function fromConfig(array $config): self
    {
        $filters = [];

        foreach ($config as $key => $definition) {
            if (is_string($definition)) {
                $filters[] = AllowedFilter::column($key, $definition);

                continue;
            }

            if (! is_array($definition)) {
                throw new InvalidArgumentException(sprintf('Invalid filter definition for key [%s].', $key));
            }

            $type = $definition['type'] ?? 'column';

            $filters[] = match ($type) {
                'column' => AllowedFilter::column($key, (string) ($definition['column'] ?? $key)),
                'json' => AllowedFilter::json(
                    $key,
                    (string) ($definition['column'] ?? ''),
                    (string) ($definition['path'] ?? ''),
                ),
                'relation' => AllowedFilter::relation(
                    $key,
                    (string) ($definition['relation'] ?? ''),
                    (string) ($definition['column'] ?? ''),
                ),
                'relation-count' => AllowedFilter::relationCount(
                    $key,
                    (string) ($definition['relation'] ?? ''),
                ),
                'scope' => AllowedFilter::scope(
                    $key,
                    (string) ($definition['scope'] ?? ''),
                ),
                default => throw new InvalidArgumentException(sprintf(
                    'Unsupported filter type [%s] for key [%s].',
                    $type,
                    $key,
                )),
            };
        }

        return new self($filters);
    }

    public function get(string $key): ?AllowedFilter
    {
        return $this->filtersByKey[$key] ?? null;
    }
}
