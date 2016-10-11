<?php

namespace DamianTW\MySQLScout\Engines\Modes;

use Laravel\Scout\Builder;
use DamianTW\MySQLScout\Services\ModelService;

class LikeExpanded extends Mode
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

        $words = explode(' ', $this->builder->query);

        $queryString .= '(';

        $itr = 0;

        foreach ($this->fields as $field) {
            foreach ($words as $word) {
                $queryString .= "`$field` LIKE :_search$itr OR ";
                $itr++;
            }
        }

        $queryString = trim($queryString, 'OR ');
        $queryString .= ')';

        return$queryString;

    }

    public function buildParams()
    {
        $words = explode(' ', $this->builder->query);

        $itr = 0;
        for ($i = 0; $i < count($this->fields); $i++) {
            foreach ($words as $word) {
                $this->whereParams["_search$itr"] = '%' . $word . '%';
                $itr ++;
            }
        }

        return $this->whereParams;

    }

    public function isFullText()
    {
        return false;
    }
}