<?php

namespace DamianTW\MySQLScout\Engines\Modes;

use Laravel\Scout\Builder;

abstract class Mode
{
    protected $builder;

    function __construct(Builder $builder)
    {
        $this->builder = $builder;
    }

    abstract public function buildWhereRawString();

    abstract public function buildParams();

    abstract public function isFullText();

}
