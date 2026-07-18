<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Enums;

enum ImportFormat: string
{
    case Csv = 'csv';
    case Xlsx = 'xlsx';
    case Json = 'json';
    case Ndjson = 'ndjson';
}
