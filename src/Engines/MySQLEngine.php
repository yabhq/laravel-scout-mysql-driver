<?php

namespace Yab\MySQLScout\Engines;

use Yab\MySQLScout\Engines\Modes\ModeContainer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Laravel\Scout\Builder;
use Laravel\Scout\Engines\Engine;

class MySQLEngine extends Engine
{
    protected $mode;

    protected $fallbackMode;

    public function __construct(ModeContainer $modeContainer)
    {
        $this->mode = $modeContainer->mode;
        $this->fallbackMode = $modeContainer->fallbackMode;
    }

    public function update($models)
    {
    }

    public function delete($models)
    {
    }

    /**
     * Pluck and return the primary keys of the given results.
     *
     * @param  mixed  $results
     * @return \Illuminate\Support\Collection
     */
    public function mapIds($results)
    {
        return collect($results['results'])->map(function ($result) {
            return $result->getKey();
        });
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

        if ($this->shouldNotRun($builder)) {
            $result['results'] = Collection::make();
            $result['count'] = 0;

            return $result;
        }

        $mode = $this->shouldUseFallback($builder) ? $this->fallbackMode : $this->mode;

        $whereRawString = $mode->buildWhereRawString($builder);
        $params = $mode->buildParams($builder);

        $model = $builder->model;
        $query = $model::whereRaw($whereRawString, $params);
        if ($mode->isFullText()) {
            $query = $query->selectRaw(DB::raw($mode->buildSelectColumns($builder)), $params);
        }

        if($builder->callback){
            $query = call_user_func($builder->callback, $query, $this);
        }

        $result['count'] = $query->count();

        if (property_exists($builder, 'orders') && !empty($builder->orders)) {
            foreach ($builder->orders as $order) {
                $query->orderBy($order['column'], $order['direction']);
            }
        }

        if ($builder->limit) {
            $query = $query->take($builder->limit);
        }

        if (property_exists($builder, 'offset') && $builder->offset) {
            $query = $query->skip($builder->offset);
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
     * @param Laravel\Scout\Builder               $builder
     * @param \Illuminate\Database\Eloquent\Model $model
     *
     * @return Collection
     */
    public function map(Builder $builder, $results, $model)
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

    /**
     * Flush all of the model's records from the engine.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * 
     * @return void
     */
    public function flush($model) 
    {
    }

    protected function shouldNotRun($builder)
    {
        return strlen($builder->query) < config('scout.mysql.min_search_length');
    }

    protected function shouldUseFallback($builder)
    {
        return $this->mode->isFullText() &&
        strlen($builder->query) < config('scout.mysql.min_fulltext_search_length');
    }
}
