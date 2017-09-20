<?php

namespace Yab\MySQLScout\Services;

use Illuminate\Support\Facades\DB;

class ModelService
{
    public $model;

    public $connectionName;

    public $tableName;

    public $indexName;

    protected $fullTextIndexTypes = ['VARCHAR', 'TEXT', 'CHAR'];

    public function setModel($model)
    {
        $modelInstance = new $model();

        $this->model = $model;

        $this->connectionName = $modelInstance->getConnectionName() !== null ?
            $modelInstance->getConnectionName() : config('database.default');

        $this->tableName = config("database.connections.$this->connectionName.prefix", '').$modelInstance->getTable();

        $this->indexName = $modelInstance->searchableAs();

        return $this;
    }

    public function getFullTextIndexFields()
    {
        $searchableFields = $this->getSearchableFields();
        $indexFields = [];

        foreach ($searchableFields as $searchableField) {

            //@TODO cache this.
            $sql = "SHOW FIELDS FROM $this->tableName where Field = ?";
            $column = DB::connection($this->connectionName)->select($sql, [$searchableField]);

            if (!isset($column[0])) {
                continue;
            }

            $columnType = $column[0]->Type;

            // When using `$appends` to include an accessor for a field that doesn't exist,
            // an ErrorException will be thrown for `Undefined Offset: 0`
            if ($this->isFullTextSupportedColumnType($columnType)) {
                $indexFields[] = $searchableField;
            }
        }

        return $indexFields;
    }

    public function getSearchableFields()
    {
        $columns = $this->getAllFields();

        return array_keys((new $this->model())->forceFill($columns)->toSearchableArray());
    }

    protected function getAllFields()
    {
        $columns = [];

        //@TODO cache this
        foreach (DB::connection($this->connectionName)->getSchemaBuilder()->getColumnListing($this->tableName) as $column) {
            $columns[$column] = null;
        }

        return $columns;
    }

    protected function isFullTextSupportedColumnType($columnType)
    {
        foreach ($this->fullTextIndexTypes as $fullTextIndexType) {
            if (stripos($columnType, $fullTextIndexType) !== false) {
                return true;
            }
        }

        return false;
    }
}
