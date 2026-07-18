<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Sorting;

use InvalidArgumentException;

final class SortAllowlist
{
    /**
     * @var array<string, AllowedSort>
     */
    private array $sortsByKey;

    /**
     * @param array<AllowedSort> $sorts
     */
    public function __construct(array $sorts)
    {
        $this->sortsByKey = [];

        foreach ($sorts as $sort) {
            $this->sortsByKey[$sort->key] = $sort;
        }
    }

    /**
     * @param array<string, string|array<string, string>> $config
     */
    public static function fromConfig(array $config): self
    {
        $sorts = [];

        foreach ($config as $key => $definition) {
            if (is_string($definition)) {
                $sorts[] = AllowedSort::column($key, $definition);

                continue;
            }

            if (! is_array($definition)) {
                throw new InvalidArgumentException(sprintf('Invalid sort definition for key [%s].', $key));
            }

            $type = $definition['type'] ?? 'column';

            $sorts[] = match ($type) {
                'column' => AllowedSort::column($key, (string) ($definition['column'] ?? $key)),
                'relation-subquery' => AllowedSort::relationSubquery(
                    $key,
                    (string) ($definition['table'] ?? ''),
                    (string) ($definition['owner_key'] ?? ''),
                    (string) ($definition['foreign_key'] ?? ''),
                    (string) ($definition['column'] ?? ''),
                ),
                'custom' => AllowedSort::custom($key, (string) ($definition['strategy'] ?? '')),
                default => throw new InvalidArgumentException(sprintf(
                    'Unsupported sort type [%s] for key [%s].',
                    $type,
                    $key,
                )),
            };
        }

        return new self($sorts);
    }

    public function get(string $key): ?AllowedSort
    {
        return $this->sortsByKey[$key] ?? null;
    }
}
