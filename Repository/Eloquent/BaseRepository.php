<?php
namespace Lyndon\Repository\Eloquent;

use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Lyndon\Repository\Contracts\CriteriaInterface;
use Lyndon\Repository\Contracts\RepositoryCriteriaInterface;
use Lyndon\Repository\Contracts\RepositoryInterface;
use Lyndon\Exceptions\RepositoryException;

/**
 * Class BaseRepository
 * @package Lyndon\Repository\Eloquent
 */
abstract class BaseRepository implements RepositoryInterface, RepositoryCriteriaInterface
{
    /**
     * @var Model
     */
    public $model;

    /**
     * @var Container
     */
    protected $app;

    /**
     * @var array
     */
    protected $fieldSearchable = [];

    /**
     * @var array
     */
    protected $aliasFieldSearchable = [];

    /**
     * @var array
     */
    protected $aliasOrderByFields = [];

    /**
     * @var array
     */
    protected $defaultOrderByFields = [];

    /**
     * array of Criteria
     *
     * @var array
     */
    protected $criteria = [];

    /**
     * @var bool
     */
    protected $skipCriteria = false;

    /**
     * BaseRepository constructor.
     * @param Container $app
     * @throws RepositoryException
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function __construct(Container $app)
    {
        $this->app = $app;
        $this->makeModel();
        $this->boot();
    }

    /**
     *
     */
    public function boot()
    {
        //
    }

    /**
     * Push Criteria for filter the query
     *
     * @param $criteria
     *
     * @return $this
     * @throws RepositoryException
     */
    public function pushCriteria($criteria)
    {
        if (is_string($criteria)) {
            $criteria = new $criteria;
        }
        if (!$criteria instanceof CriteriaInterface) {
            throw new RepositoryException("Class " . get_class($criteria) . " must be an instance of Lyndon\\Repository\\Contracts\\CriteriaInterface");
        }

        array_push($this->criteria, $criteria);

        return $this;
    }

    /**
     * Pop Criteria
     *
     * @param $criteria
     *
     * @return $this
     */
    public function popCriteria($criteria)
    {
        $this->criteria = array_filter($this->criteria, function ($item) use ($criteria) {
            if (is_object($item) && is_string($criteria)) {
                return get_class($item) !== $criteria;
            }

            if (is_string($item) && is_object($criteria)) {
                return $item !== get_class($criteria);
            }

            return get_class($item) !== get_class($criteria);
        });
        $this->criteria = array_values($this->criteria);

        return $this;
    }

    /**
     * Get Array of Criteria
     *
     * @return array
     */
    public function getCriteria()
    {
        return $this->criteria;
    }

    /**
     * Skip Criteria
     *
     * @param bool $status
     *
     * @return $this
     */
    public function skipCriteria($status = true)
    {
        $this->skipCriteria = $status;

        return $this;
    }

    /**
     * Reset all Criteria
     *
     * @return $this
     */
    public function resetCriteria()
    {
        $this->criteria = [];

        return $this;
    }

    /**
     * Apply criteria in current Query
     *
     * @return $this
     */
    public function applyCriteria()
    {
        if ($this->skipCriteria === true) {
            return $this;
        }

        $criteria = $this->getCriteria();

        if (! empty($criteria)) {
            foreach ($criteria as $c) {
                if ($c instanceof CriteriaInterface) {
                    $this->model = $c->apply($this->model, $this);
                }
            }
        }

        return $this;
    }

    /**
     * Get Searchable Fields
     *
     * @return array
     */
    public function getFieldsSearchable()
    {
        return $this->fieldSearchable;
    }

    /**
     * 获取可搜索字段的别名
     * key是别名，value是fieldSearchable属性元素
     *
     * @return array
     */
    public function getAliasFieldsSearchable()
    {
        return $this->aliasFieldSearchable;
    }

    /**
     * 获取排序字段的别名
     *
     * @return array
     */
    public function getAliasOrderByFields()
    {
        return $this->aliasOrderByFields;
    }

    /**
     * 默认排序列表，在未传orderBy参数时启用
     *
     * @return array
     */
    public function defaultOrderByFields()
    {
        return $this->defaultOrderByFields;
    }

    /**
     * @throws RepositoryException
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function resetModel()
    {
        $this->makeModel();
    }

    /**
     * Specify Model class name
     *
     * @return string
     */
    abstract public function model();

    /**
     * @return Model|mixed
     * @throws RepositoryException
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function makeModel()
    {
        $model = $this->app->make($this->model());

        if (!$model instanceof Model) {
            throw new RepositoryException("Class {$this->model()} must be an instance of Illuminate\\Database\\Eloquent\\Model");
        }

        return $this->model = $model;
    }
}
