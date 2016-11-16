<?php

namespace DamianTW\MySQLScout\Engines\Modes;

use Laravel\Scout\Builder;

class NaturalLanguage extends Mode
{
    public function buildWhereRawString(Builder $builder)
    {
        $queryString = '';

        $queryString .= $this->buildWheres($builder);

        $indexFields = implode(',',  $this->modelService->setModel($builder->model)->getFullTextIndexFields());

        $queryString .= "MATCH($indexFields) AGAINST(:_search IN NATURAL LANGUAGE MODE";

        if (config('scout.mysql.query_expansion')) {
            $queryString .= ' WITH QUERY EXPANSION';
        }

        $queryString .= ')';

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
