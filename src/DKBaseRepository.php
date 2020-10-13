<?php

namespace Denknows\DKBase;

use Denknows\DKBase\Exception\GeneralException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class DKBaseRepository
 *
 * @package Denknows\DKBaseRepository
 */
abstract class DKBaseRepository
{
    /**
     * The repository app.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $app;

    /**
     * The repository model.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $model;

    /**
     * The query builder.
     *
     * @var \Illuminate\Database\Eloquent\Builder
     */
    protected $query;

    /**
     * Alias for the query limit.
     *
     * @var int
     */
    protected $take;

    /**
     * Array of related models to eager load.
     *
     * @var array
     */
    protected $with = [];

    /**
     * Array of one or more where clause parameters.
     *
     * @var array
     */
    protected $wheres = [];

    /**
     * Array of one or more where in clause parameters.
     *
     * @var array
     */
    protected $whereIns = [];

    /**
     * Array of one or more ORDER BY column/value pairs.
     *
     * @var array
     */
    protected $orderBys = [];

    /**
     * Array of scope methods to call on the model.
     *
     * @var array
     */
    protected $scopes = [];

    /**
     * DKBaseRepository constructor.
     *
     * @throws GeneralException
     */
    public function __construct(Container $app)
    {
        $this->app = $app;
        $this->model = $this->makeModel();
    }

    /**
     * Specify Model class name.
     *
     * @return mixed
     */
    abstract public function model();

    /**
     * @param array|string[] $columns
     * @return \Illuminate\Database\Eloquent\Builder[]|Collection
     */
    public function all(array $columns = ['*'])
    {
        $this->newQuery()->eagerLoad();

        $models = $this->query->get($columns);

        $this->unsetClauses();

        return $models;
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return $this->model->count();
    }

    /**
     * @param array $data
     * @return mixed
     */
    public function create(array $data)
    {
        $this->unsetClauses();

        return $this->model->create($data);
    }

    /**
     * @param array $data
     * @return Collection
     */
    public function createMultiple(array $data)
    {
        $models = new Collection();

        foreach ($data as $d) {
            $models->push($this->create($d));
        }

        return $models;
    }

    /**
     * @return mixed
     */
    public function delete()
    {
        $this->newQuery()->setClauses()->setScopes();

        $result = $this->query->delete();

        $this->unsetClauses();

        return $result;
    }

    /**
     * @param $id
     * @return bool
     * @throws \Exception
     */
    public function deleteById($id): bool
    {
        $this->unsetClauses();

        return $this->getById($id)->delete();
    }

    /**
     * @param array $ids
     * @return int
     */
    public function deleteMultipleById(array $ids): int
    {
        return $this->model->destroy($ids);
    }

    /**
     * @param array|string[] $columns
     * @return \Illuminate\Database\Eloquent\Builder|Model
     */
    public function first(array $columns = ['*'])
    {
        $this->newQuery()->eagerLoad()->setClauses()->setScopes();

        $model = $this->query->firstOrFail($columns);

        $this->unsetClauses();

        return $model;
    }

    /**
     * @param array|string[] $columns
     * @return \Illuminate\Database\Eloquent\Builder[]|Collection
     */
    public function get(array $columns = ['*'])
    {
        $this->newQuery()->eagerLoad()->setClauses()->setScopes();

        $models = $this->query->get($columns);

        $this->unsetClauses();

        return $models;
    }

    /**
     * @param $id
     * @param array|string[] $columns
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Builder[]|Collection|Model|null
     */
    public function getById($id, array $columns = ['*'])
    {
        $this->unsetClauses();

        $this->newQuery()->eagerLoad();

        return $this->query->findOrFail($id, $columns);
    }

    /**
     * @param $item
     * @param $column
     * @param array|string[] $columns
     * @return \Illuminate\Database\Eloquent\Builder|Model|object|null
     */
    public function getByColumn($item, $column, array $columns = ['*'])
    {
        $this->unsetClauses();

        $this->newQuery()->eagerLoad();

        return $this->query->where($column, $item)->first($columns);
    }

    /**
     * @param int $limit
     * @param array|string[] $columns
     * @param string $pageName
     * @param null $page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate($limit = 25, array $columns = ['*'], $pageName = 'page', $page = null)
    {
        $this->newQuery()->eagerLoad()->setClauses()->setScopes();

        $models = $this->query->paginate($limit, $columns, $pageName, $page);

        $this->unsetClauses();

        return $models;
    }

    /**
     * @param $id
     * @param array $data
     * @param array $options
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Builder[]|Collection|Model|null
     */
    public function updateById($id, array $data, array $options = [])
    {
        $this->unsetClauses();

        $model = $this->getById($id);

        $model->update($data, $options);

        return $model;
    }

    /**
     * @param $limit
     * @return $this
     */
    public function limit($limit)
    {
        $this->take = $limit;

        return $this;
    }

    /**
     * @param $column
     * @param string $direction
     * @return $this
     */
    public function orderBy($column, $direction = 'asc')
    {
        $this->orderBys[] = compact('column', 'direction');

        return $this;
    }

    /**
     * @param $column
     * @param $value
     * @param string $operator
     * @return $this
     */
    public function where($column, $value, $operator = '=')
    {
        $this->wheres[] = compact('column', 'value', 'operator');

        return $this;
    }

    /**
     * @param $column
     * @param $values
     * @return $this
     */
    public function whereIn($column, $values)
    {
        $values = is_array($values) ? $values : [$values];

        $this->whereIns[] = compact('column', 'values');

        return $this;
    }

    /**
     * @param $relations
     * @return $this
     */
    public function with($relations)
    {
        if (is_string($relations)) {
            $relations = func_get_args();
        }

        $this->with = $relations;

        return $this;
    }

    /**
     * @return $this
     */
    protected function newQuery()
    {
        $this->query = $this->model->newQuery();

        return $this;
    }

    /**
     * @return $this
     */
    protected function eagerLoad()
    {
        foreach ($this->with as $relation) {
            $this->query->with($relation);
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function setClauses()
    {
        foreach ($this->wheres as $where) {
            $this->query->where($where['column'], $where['operator'], $where['value']);
        }

        foreach ($this->whereIns as $whereIn) {
            $this->query->whereIn($whereIn['column'], $whereIn['values']);
        }

        foreach ($this->orderBys as $orders) {
            $this->query->orderBy($orders['column'], $orders['direction']);
        }

        if (isset($this->take) and !is_null($this->take)) {
            $this->query->take($this->take);
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function setScopes()
    {
        foreach ($this->scopes as $method => $args) {
            $this->query->$method(...$args);
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function unsetClauses()
    {
        $this->wheres = [];
        $this->whereIns = [];
        $this->scopes = [];
        $this->take = null;

        return $this;
    }

    /**
     * @param $scope
     * @param $args
     * @return $this
     */
    public function __call($scope, $args)
    {
        $this->scopes[$scope] = $args;

        return $this;
    }

    /**
     * @param $column
     * @param $key
     * @return \Illuminate\Support\Collection
     */
    public function pluck($column, $key = [])
    {
        $this->newQuery();

        $results = $this->query->pluck($column, $key);

        $this->unsetClauses();

        return $results;
    }

    /**
     * @return mixed
     * @throws Exception
     */
    private function makeModel()
    {
        $model = $this->app->make($this->model());

        if (!$model instanceof Model) {
            throw new Exception("Class {$this->model()} must be an instance of Illuminate\\Database\\Eloquent\\Model");
        }

        return $model;
    }
}
