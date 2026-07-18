<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Enums;

enum FilterOperator: string
{
    case Eq = 'eq';
    case Neq = 'neq';
    case In = 'in';
    case NotIn = 'not-in';
    case Contains = 'contains';
    case StartsWith = 'starts-with';
    case EndsWith = 'ends-with';
    case Between = 'between';
    case DateRange = 'date-range';
    case IsNull = 'null';
    case IsNotNull = 'not-null';
}
