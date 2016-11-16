<?php

namespace DamianTW\MySQLScout\Engines\Modes;

use Laravel\Scout\Builder;
use DamianTW\MySQLScout\Services\ModelService;

class Boolean extends Mode
{
    public function buildWhereRawString(Builder $builder)
    {
        $queryString = '';

        $queryString .= $this->buildWheres($builder);

        $indexFields = implode(',',  $this->modelService->setModel($builder->model)->getFullTextIndexFields());

        $queryString .= "MATCH($indexFields) AGAINST(:_search IN BOOLEAN MODE)";

        return $queryString;
    }

    public function buildParams(Builder $builder)
    {
        $this->whereParams['_search'] = $builder->query;

        return $this->whereParams;
    }

    public function isFullText()
    {
        return true;
    }
}
