<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Enums;

enum ExportFormat: string
{
    case Csv = 'csv';
    case Xlsx = 'xlsx';
    case Pdf = 'pdf';
    case Json = 'json';
    case Ndjson = 'ndjson';
    case Xml = 'xml';
    case Parquet = 'parquet';
}
