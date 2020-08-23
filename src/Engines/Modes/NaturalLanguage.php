<?php

namespace Yab\MySQLScout\Engines\Modes;

use Laravel\Scout\Builder;

class NaturalLanguage extends Mode
{
    public function buildWhereRawString(Builder $builder)
    {
        return $this->buildWheres($builder) . $this->buildMatchQuery($builder);
    }

    public function buildSelectColumns(Builder $builder)
    {
        $matchQuery = $this->buildMatchQuery($builder);

        return "*, $matchQuery as relevance";
    }

    private function buildMatchQuery(Builder $builder)
    {
        $indexFields = implode(',',  $this->modelService->setModel($builder->model)->getFullTextIndexFields());

        $queryString = "MATCH($indexFields) AGAINST(? IN NATURAL LANGUAGE MODE";

        if (config('scout.mysql.query_expansion')) {
            $queryString .= ' WITH QUERY EXPANSION';
        }

        $queryString .= ')';

        return $queryString;
    }

    public function buildParams(Builder $builder)
    {
        $this->whereParams[] = $builder->query;

        return $this->whereParams;
    }

    public function isFullText()
    {
        return true;
    }
}
