<?php

declare(strict_types=1);

namespace Yoosuf\LaravelDataFlow\DataTransferObjects;

use Illuminate\Database\Eloquent\Builder;

final readonly class QuerySpecification
{
    /**
     * @param array<mixed> $bindings
     */
    public function __construct(
        public string $modelClass,
        public ?string $connectionName,
        public string $keyName,
        public string $idSubquerySql,
        public array $bindings,
    ) {
    }

    public static function fromBuilder(Builder $query): self
    {
        $model = $query->getModel();
        $keyName = $model->getKeyName();

        $idQuery = clone $query;
        $idQuery->reorder();
        $idQuery->select($model->qualifyColumn($keyName))->distinct();

        return new self(
            modelClass: $model::class,
            connectionName: $model->getConnectionName(),
            keyName: $keyName,
            idSubquerySql: $idQuery->toBase()->toSql(),
            bindings: $idQuery->toBase()->getBindings(),
        );
    }

    public function toBuilder(): Builder
    {
        /** @var \Illuminate\Database\Eloquent\Model $model */
        $model = new $this->modelClass();

        if ($this->connectionName !== null) {
            $model->setConnection($this->connectionName);
        }

        $qualifiedKey = $model->qualifyColumn($this->keyName);
        $subqueryAlias = 'dataflow_spec';

        return $model->newQuery()->whereIn($qualifiedKey, function ($query) use ($subqueryAlias): void {
            $query->fromRaw('('.$this->idSubquerySql.') as '.$subqueryAlias, $this->bindings)
                ->select($subqueryAlias.'.'.$this->keyName);
        });
    }
}
