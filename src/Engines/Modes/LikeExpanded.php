<?php

namespace DamianTW\MySQLScout\Engines\Modes;

use Laravel\Scout\Builder;
use DamianTW\MySQLScout\Services\ModelService;

class LikeExpanded extends Mode
{
    protected $fields;

    public function buildWhereRawString(Builder $builder)
    {
        $queryString = '';

        $this->fields = $this->modelService->setModel($builder->model)->getSearchableFields();

        $queryString .= $this->buildWheres($builder);

        $words = explode(' ', $builder->query);

        $queryString .= '(';

        $itr = 0;

        foreach ($this->fields as $field) {
            foreach ($words as $word) {
                $queryString .= "`$field` LIKE :_search$itr OR ";
                ++$itr;
            }
        }

        $queryString = trim($queryString, 'OR ');
        $queryString .= ')';

        return$queryString;
    }

    public function buildParams(Builder $builder)
    {
        $words = explode(' ', $builder->query);

        $itr = 0;
        for ($i = 0; $i < count($this->fields); ++$i) {
            foreach ($words as $word) {
                $this->whereParams["_search$itr"] = '%'.$word.'%';
                ++$itr;
            }
        }

        return $this->whereParams;
    }

    public function isFullText()
    {
        return false;
    }
}
