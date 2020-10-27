<?php
namespace Lyndon\Repository\Eloquent;


use Lyndon\Model\BaseModel;
use Illuminate\Container\Container;
use Lyndon\Exceptions\RepositoryException;
use Lyndon\Repository\Contracts\CriteriaInterface;
use Lyndon\Repository\Contracts\RepositoryInterface;
use Lyndon\Repository\Contracts\RepositoryCriteriaInterface;

/**
 * Class BaseRepository
 * @package Lyndon\Repository\Eloquent
 *
 * @property BaseModel $model
 */
abstract class BaseRepository implements RepositoryInterface, RepositoryCriteriaInterface
{
    use RepositoryCommonMethodsTrait, RepositoryCriteriaTrait;

    /**
     * @var BaseModel
     */
    public $model;

    /**
     * @var Container
     */
    protected $app;

    /**
     * 标准查询字段
     *
     * @var array
     */
    protected $fieldSearchable = [];

    /**
     * 标准查询字段别名
     *
     * @var array
     */
    protected $aliasFieldSearchable = [];

    /**
     * 排序字段别名
     *
     * @var array
     */
    protected $aliasOrderByFields = [];

    /**
     * 默认排序字段
     *
     * @var array
     */
    protected $defaultOrderByFields = [];

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
     * @return BaseModel|mixed
     * @throws RepositoryException
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function makeModel()
    {
        $model = $this->app->make($this->model());

        if (!$model instanceof BaseModel) {
            throw new RepositoryException("Class {$this->model()} must be an instance of Lyndon\\Model\\BaseModel");
        }

        return $this->model = $model;
    }
}
