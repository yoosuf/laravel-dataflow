<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Enums;

enum LogicalOperator: string
{
    case And = 'and';
    case Or = 'or';
}
