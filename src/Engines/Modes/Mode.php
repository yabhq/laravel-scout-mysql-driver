<?php

namespace Yab\MySQLScout\Engines\Modes;

use Laravel\Scout\Builder;
use Yab\MySQLScout\Services\ModelService;

abstract class Mode
{
    protected $whereParams = [];

    /**
     * @var ModelService
     */
    protected $modelService;

    public function __construct()
    {
        $this->modelService = app(ModelService::class);
    }

    abstract public function buildWhereRawString(Builder $builder);

    abstract public function buildParams(Builder $builder);

    abstract public function isFullText();

    protected function buildWheres(Builder $builder)
    {
        $this->whereParams = null;

        $queryString = '';

        $parsedWheres = $this->parseWheres($builder->wheres);

        foreach ($parsedWheres as $parsedWhere) {
            $field = $parsedWhere[0];
            $operator = $parsedWhere[1];
            $value = $parsedWhere[2];

            if ($value !== null) {
                $this->whereParams[$field] = $value;
                $queryString .= "$field $operator ? AND ";
            } else {
                $queryString .= "$field IS NULL AND ";
            }
        }

        return $queryString;
    }

    private function parseWheres($wheres)
    {
        $pattern = '/([A-Za-z_]+[A-Za-z_0-9]?)[ ]?(<>|!=|=|<=|<|>=|>)/';

        $result = array();
        foreach ($wheres as $field => $value) {
            preg_match($pattern, $field, $matches);
            $result [] = !empty($matches) ? array($matches[1], $matches[2], $value) : array($field, '=', $value);
        }

        /**
         * Add support for where() on json columns using '->' syntax
         * data->a->b->c translates to json_unquote(json_extract(`data`, '$."a"."b"."c"'))
         */
        foreach ($result as $_k => $_v) {
            if (($_v[0] ?? false) && stripos($_v[0],'->')!==false) {
                list($root,$path) = explode('->',$_v[0],2);
                $result[$_k][0] = "json_unquote(json_extract(`$root`, '$.\"".implode('"."',explode('->',$path))."\"'))";
            }
        }
        
        return $result;
    }
}
