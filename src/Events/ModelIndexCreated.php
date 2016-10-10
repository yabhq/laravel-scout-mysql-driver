<?php

namespace DamianTW\MySQLScout\Events;


class ModelIndexCreated
{

    public $indexName;
    public $indexFields;

    /**
     * Create a new event instance.
     *
     * @param $indexName
     * @param $indexFields
     * @return void
     */
    public function __construct($indexName, $indexFields)
    {
        $this->indexName = $indexName;
        $this->indexFields = $indexFields;
    }

}
