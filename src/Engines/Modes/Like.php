<?php

namespace Yab\MySQLScout\Engines\Modes;

use Laravel\Scout\Builder;
use Yab\MySQLScout\Services\ModelService;

class Like extends Mode
{
    protected $fields;

    public function buildWhereRawString(Builder $builder)
    {
        $queryString = '';

        $this->fields = $this->modelService->setModel($builder->model)->getSearchableFields();

        $queryString .= $this->buildWheres($builder);

        $queryString .= '(';

        foreach ($this->fields as $field) {
            $queryString .= "`$field` LIKE ? OR ";
        }

        $queryString = trim($queryString, 'OR ');
        $queryString .= ')';

        return $queryString;
    }

    public function buildParams(Builder $builder)
    {
        for ($itr = 0; $itr < count($this->fields); ++$itr) {
            $this->whereParams[] = '%'.$builder->query.'%';
        }

        return $this->whereParams;
    }

    public function isFullText()
    {
        return false;
    }
}
