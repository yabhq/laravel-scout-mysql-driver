<?php

namespace DamianTW\MySQLScout\Engines;

use Illuminate\Database\Eloquent\Collection;
use Laravel\Scout\Builder;
use Laravel\Scout\Engines\Engine;

class MySQLEngine extends Engine
{

    protected $mode;

    protected $builder;

    public function setup(Builder $builder)
    {
        $this->builder = $builder;

        $mode = __NAMESPACE__ . '\\Modes\\' . studly_case(strtolower(config('scout.mysql.mode')));
        $this->mode = new $mode($this->builder);

        if($this->shouldUseFallback()) {
            $mode = __NAMESPACE__ . '\\Modes\\' . studly_case(strtolower(config('scout.mysql.min_fulltext_search_fallback')));
            $this->mode = new $mode($this->builder);
        }
    }

    public function update($models)
    {

    }

    public function delete($models)
    {

    }

    /**
     * Perform the given search on the engine.
     *
     * @param Builder $builder
     *
     * @return mixed
     */
    public function search(Builder $builder)
    {

        $result = [];

        $this->setup($builder);

        if($this->shouldNotRun()) {
            $result['results'] = Collection::make();
            $result['count'] = 0;
            return $result;
        }


        $model = $this->builder->model;

        $whereRawString = $this->mode->buildWhereRawString();
        $params = $this->mode->buildParams();

        $query = $model::whereRaw($whereRawString, $params);

        $result['count'] = $query->count();

        if($this->builder->limit) {
            $query = $query->take($this->builder->limit);
        }

        if(property_exists($this->builder, 'offset') && $this->builder->offset) {
            $query = $query->skip($this->builder->offset);
        }

        $result['results'] = $query->get();

        return $result;

    }

    /**
     * Perform the given search on the engine.
     *
     * @param Builder $builder
     * @param int     $perPage
     * @param int     $page
     *
     * @return mixed
     */
    public function paginate(Builder $builder, $perPage, $page)
    {
        $builder->limit = $perPage;
        $builder->offset = ($perPage * $page) - $perPage;
        return $this->search($builder);
    }

    /**
     * Map the given results to instances of the given model.
     *
     * @param mixed                               $results
     * @param \Illuminate\Database\Eloquent\Model $model
     *
     * @return Collection
     */
    public function map($results, $model)
    {
        return $results['results'];
    }

    /**
     * Get the total count from a raw result returned by the engine.
     *
     * @param mixed $results
     *
     * @return int
     */
    public function getTotalCount($results)
    {
        return $results['count'];
    }

    protected function shouldNotRun()
    {
        return strlen($this->builder->query) < config('scout.mysql.min_search_length');
    }

    protected function shouldUseFallback()
    {
        return $this->mode->isFullText() &&
        strlen($this->builder->query) < config('scout.mysql.min_fulltext_search_length');
    }

}