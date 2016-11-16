<?php

namespace DamianTW\MySQLScout\Engines\Modes;

use Laravel\Scout\Builder;
use DamianTW\MySQLScout\Services\ModelService;

class Like extends Mode
{
    protected $fields;

    public function buildWhereRawString(Builder $builder)
    {
        $queryString = '';

        $this->fields = $this->modelService->setModel($builder->model)->getSearchableFields();

        $queryString .= $this->buildWheres($builder);

        $queryString .= '(';

        $itr = 0;
        foreach ($this->fields as $field) {
            $queryString .= "`$field` LIKE :_search$itr OR ";
            ++$itr;
        }

        $queryString = trim($queryString, 'OR ');
        $queryString .= ')';

        return $queryString;
    }

    public function buildParams(Builder $builder)
    {
        for ($itr = 0; $itr < count($this->fields); ++$itr) {
            $this->whereParams["_search$itr"] = '%'.$builder->query.'%';
        }

        return $this->whereParams;
    }

    public function isFullText()
    {
        return false;
    }
}
