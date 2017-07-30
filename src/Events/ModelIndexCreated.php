<?php

namespace Yab\MySQLScout\Events;

class ModelIndexCreated
{
    public $indexName;
    public $indexFields;

    /**
     * Create a new event instance.
     *
     * @param $indexName
     * @param $indexFields
     */
    public function __construct($indexName, $indexFields)
    {
        $this->indexName = $indexName;
        $this->indexFields = $indexFields;
    }
}
