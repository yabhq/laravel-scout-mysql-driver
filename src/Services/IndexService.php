<?php

namespace Yab\MySQLScout\Services;

use Laravel\Scout\Searchable;
use Illuminate\Support\Facades\DB;
use Yab\MySQLScout\Events;
use Illuminate\Container\Container;

class IndexService
{
    protected $modelService;

    public function __construct(ModelService $modelService)
    {
        $this->modelService = $modelService;
    }

    public function setModel($model)
    {
        $this->modelService->setModel($model);
    }

    public function getAllSearchableModels($directories)
    {
        $searchableModels = [];

        foreach ($directories as $directory) {
            $files = glob($directory.'/*.php');

            foreach ($files as $file) {
                $class = getClassFullNameFromFile($file);

                if (! class_exists($class) || ! in_array(Searchable::class, class_uses($class))) {
                    continue;
                }

                $modelInstance = new $class();

                $connectionName = $modelInstance->getConnectionName() !== null ?
                    $modelInstance->getConnectionName() : config('database.default');

                $isMySQL = config("database.connections.$connectionName.driver") === 'mysql';

                if ($isMySQL) {
                    $searchableModels[] = $class;
                }
            }
        }

        return $searchableModels;
    }

    public function createOrUpdateIndex()
    {
        if ($this->indexAlreadyExists()) {
            if ($this->indexNeedsUpdate()) {
                $this->updateIndex();
            } else {
                event(new Events\ModelIndexIgnored($this->modelService->indexName));
            }
        } else {
            $this->createIndex();
        }
    }

    protected function createIndex()
    {
        $indexName = $this->modelService->indexName;
        $tableName = $this->modelService->tablePrefixedName;
        $indexFields = implode(',', array_map(function($indexField) {
            return "`$indexField`";
        }, $this->modelService->getFullTextIndexFields()));

        if (empty($indexFields)) {
            return;
        }

        DB::connection($this->modelService->connectionName)
            ->statement("CREATE FULLTEXT INDEX $indexName ON $tableName ($indexFields)");

        event(new Events\ModelIndexCreated($indexName, $indexFields));
    }

    protected function indexAlreadyExists()
    {
        $tableName = $this->modelService->tablePrefixedName;
        $indexName = $this->modelService->indexName;

        return !empty(DB::connection($this->modelService->connectionName)->
        select("SHOW INDEX FROM $tableName WHERE Key_name = ?", [$indexName]));
    }

    protected function indexNeedsUpdate()
    {
        $currentIndexFields = $this->modelService->getFullTextIndexFields();
        $expectedIndexFields = $this->getIndexFields();

        return $currentIndexFields != $expectedIndexFields;
    }

    protected function getIndexFields()
    {
        $indexName = $this->modelService->indexName;
        $tableName = $this->modelService->tablePrefixedName;

        $index = DB::connection($this->modelService->connectionName)->
        select("SHOW INDEX FROM $tableName WHERE Key_name = ?", [$indexName]);

        $indexFields = [];

        foreach ($index as $idx) {
            $indexFields[] = $idx->Column_name;
        }

        return $indexFields;
    }

    public function getAppNamespace()
    {
        return Container::getInstance()->getNamespace();
    }

    protected function updateIndex()
    {
        $this->dropIndex();
        $this->createOrUpdateIndex();
        event(new Events\ModelIndexUpdated($this->modelService->indexName));
    }

    public function dropIndex()
    {
        $indexName = $this->modelService->indexName;
        $tableName = $this->modelService->tablePrefixedName;

        if ($this->indexAlreadyExists()) {
            DB::connection($this->modelService->connectionName)
                ->statement("ALTER TABLE $tableName DROP INDEX $indexName");
            event(new Events\ModelIndexDropped($this->modelService->indexName));
        }
    }
}
