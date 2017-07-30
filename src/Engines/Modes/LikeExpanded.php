<?php

namespace Yab\MySQLScout\Engines\Modes;

use Laravel\Scout\Builder;
use Yab\MySQLScout\Services\ModelService;

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

        foreach ($this->fields as $field) {
            foreach ($words as $word) {
                $queryString .= "`$field` LIKE ? OR ";
            }
        }

        $queryString = trim($queryString, 'OR ');
        $queryString .= ')';

        return$queryString;
    }

    public function buildParams(Builder $builder)
    {
        $words = explode(' ', $builder->query);

        for ($i = 0; $i < count($this->fields); ++$i) {
            foreach ($words as $word) {
                $this->whereParams[] = '%'.$word.'%';
            }
        }

        return $this->whereParams;
    }

    public function isFullText()
    {
        return false;
    }
}
