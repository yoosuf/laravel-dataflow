<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Exceptions;

use InvalidArgumentException;

final class UnsupportedFilterException extends InvalidArgumentException
{
    public static function forKey(string $key): self
    {
        return new self(sprintf('Filter [%s] is not allowlisted.', $key));
    }
}
