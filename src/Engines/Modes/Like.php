<?php

namespace DamianTW\MySQLScout\Engines\Modes;
use Laravel\Scout\Builder;
use DamianTW\MySQLScout\Services\ModelService;

class Like extends Mode
{

    protected $modelService;

    protected $fields;

    function __construct(Builder $builder)
    {
        parent::__construct($builder);

        $this->modelService = resolve(ModelService::class);
        $this->modelService->setModel($this->builder->model);
    }

    public function buildWhereRawString()
    {
        $queryString = '';

        $this->fields = $this->modelService->getSearchableFields();

        foreach ($this->builder->wheres as $field => $value) {
            $queryString .= "$field = :$field AND ";
        }

        $queryString .= '(';

        $itr = 0;
        foreach ($this->fields as $field) {
            $queryString .= "`$field` LIKE :_search$itr OR ";
            $itr++;
        }

        $queryString = trim($queryString, 'OR ');
        $queryString .= ')';

        return $queryString;

    }

    public function buildParams()
    {
        $params = [];

        foreach ($this->builder->wheres as $field => $value) {
            $params[$field] = $value;
        }

        for ($itr = 0; $itr < count($this->fields); $itr++) {
            $params["_search$itr"] = '%' . $this->builder->query . '%';
        }

        return $params;
    }

    public function isFullText()
    {
        return false;
    }
}