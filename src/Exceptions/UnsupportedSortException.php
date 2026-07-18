<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Exceptions;

use InvalidArgumentException;

final class UnsupportedSortException extends InvalidArgumentException
{
    public static function forField(string $field): self
    {
        return new self(sprintf('Sort field [%s] is not allowlisted.', $field));
    }
}
