<?php

namespace DamianTW\MySQLScout\Events;


class ModelIndexUpdated
{

    public $indexName;

    /**
     * Create a new event instance.
     *
     * @param $indexName
     * @return void
     */
    public function __construct($indexName)
    {
        $this->indexName = $indexName;
    }

}
