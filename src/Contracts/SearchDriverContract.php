<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Yoosuf\LaravelDataFlow\DataTransferObjects\SearchQuery;

interface SearchDriverContract
{
    public function apply(Builder $query, SearchQuery $searchQuery): Builder;
}
