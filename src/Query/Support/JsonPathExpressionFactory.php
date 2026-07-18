<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Query\Support;

use Illuminate\Database\Eloquent\Builder;

final class JsonPathExpressionFactory
{
    public function for(Builder $query, string $column, string $path): string
    {
        $driver = $query->getConnection()->getDriverName();
        $wrappedColumn = $query->getQuery()->getGrammar()->wrap($column);

        return match ($driver) {
            'mysql', 'mariadb' => sprintf("json_unquote(json_extract(%s, '$.%s'))", $wrappedColumn, $path),
            'pgsql' => sprintf("%s #>> '{%s}'", $wrappedColumn, str_replace('.', ',', $path)),
            'sqlsrv' => sprintf("json_value(%s, '$.%s')", $wrappedColumn, $path),
            default => sprintf("json_extract(%s, '$.%s')", $wrappedColumn, $path),
        };
    }
}
