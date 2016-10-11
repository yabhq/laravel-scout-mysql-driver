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

        $queryString .= $this->buildWheres();

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
        for ($itr = 0; $itr < count($this->fields); $itr++) {
            $this->whereParams["_search$itr"] = '%' . $this->builder->query . '%';
        }

        return $this->whereParams;
    }

    public function isFullText()
    {
        return false;
    }
}