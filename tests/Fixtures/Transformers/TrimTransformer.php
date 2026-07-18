<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Tests\Fixtures\Transformers;

final class TrimTransformer
{
    /**
     * @param array<string, mixed> $row
     */
    public function __invoke(mixed $value, array $row): mixed
    {
        return is_string($value) ? trim($value) : $value;
    }
}
