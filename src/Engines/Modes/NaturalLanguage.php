<?php

namespace DamianTW\MySQLScout\Engines\Modes;

use Laravel\Scout\Builder;
use DamianTW\MySQLScout\Services\ModelService;

class NaturalLanguage extends Mode
{
    protected $modelService;

    function __construct(Builder $builder)
    {
        parent::__construct($builder);

        $this->modelService = resolve(ModelService::class);
        $this->modelService->setModel($this->builder->model);
    }

    public function buildWhereRawString()
    {
        $queryString = '';

        foreach ($this->builder->wheres as $field => $value) {
            $queryString .= "$field = :$field AND ";
        }

        $indexFields = implode(',',  $this->modelService->getFullTextIndexFields());

        $queryString .= "MATCH($indexFields) AGAINST(:_search IN NATURAL LANGUAGE MODE";

        if(config('scout.mysql.query_expansion')) {
            $queryString .= ' WITH QUERY EXPANSION';
        }

        $queryString .= ')';

        return $queryString;

    }

    public function buildParams()
    {
        $params = [];

        foreach ($this->builder->wheres as $field => $value) {
            $params[$field] = $value;
        }

        $params['_search'] = $this->builder->query;
        return $params;
    }

    public function isFullText()
    {
        return true;
    }
}