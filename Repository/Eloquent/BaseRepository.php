<?php
namespace Lyndon\Repository\Eloquent;


use Lyndon\Model\BaseModel;
use Illuminate\Container\Container;
use Illuminate\Pagination\Paginator;
use Lyndon\Exceptions\RepositoryException;
use Illuminate\Pagination\LengthAwarePaginator;
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
     * 全局范围查询
     *
     * @var array
     */
    protected $scopeQuery = [];

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

    /**
     * 追加全局范围查询
     *
     * @param $scopeQuery
     */
    public function pushScopeQuery($scopeQuery)
    {
        $this->scopeQuery = array_merge($this->scopeQuery, $scopeQuery);
    }

    /**
     * 当前查询应用范围
     *
     * @return $this
     */
    public function applyScopeQuery()
    {
        if (! empty($this->scopeQuery)) {
            $this->model = $this->model->where($this->scopeQuery);
        }

        return $this;
    }

    /**
     * 添加数据行
     *
     * @param array $param
     * @return mixed
     * @throws \Lyndon\Exceptions\ModelException
     */
    public function addRepoRow(array $param)
    {
        return $this->model->addRow($param);
    }

    /**
     * 批量添加数据列
     *
     * @param array $param
     * @return mixed
     */
    public function batchAddRepoList(array $param)
    {
        return $this->model->insert($param);
    }

    /**
     * 编辑数据行
     *
     * @param mixed $primaryKey
     * @param array $param
     * @param array $extraWhere
     * @return mixed
     * @throws \Lyndon\Exceptions\ModelException
     */
    public function editRepoRow($primaryKey, array $param, array $extraWhere = [])
    {
        $extraWhere = array_merge($this->scopeQuery, $extraWhere);

        return $this->model->editRow($primaryKey, $param, $extraWhere);
    }

    /**
     * 获取一行数据
     *
     * @param mixed $primaryKey
     * @param array $extraWhere
     * @param string $trashed -- onlyTrashed / withTrashed
     * @return array
     */
    public function getRepoRowByPrimaryKey($primaryKey, array $extraWhere = [], $trashed = '')
    {
        $extraWhere = array_merge($this->scopeQuery, $extraWhere);

        return $this->model->getRowByPrimaryKey($primaryKey, $extraWhere, $trashed);
    }

    /**
     * 验证一行数据存在性
     *
     * @param mixed $primaryKey
     * @param array $extraWhere
     * @param string $trashed -- onlyTrashed / withTrashed
     * @return bool
     */
    public function existsRepoRowByPrimaryKey($primaryKey, array $extraWhere = [], $trashed = '')
    {
        $extraWhere = array_merge($this->scopeQuery, $extraWhere);

        return $this->model->existsRowByPrimaryKey($primaryKey, $extraWhere, $trashed);
    }

    /**
     * 获取多行数据
     *
     * @param array $primaryKeys
     * @param array $extraWhere
     * @param string $trashed -- onlyTrashed / withTrashed
     * @return array
     */
    public function getRepoListByPrimaryKeys($primaryKeys, $extraWhere = [], $trashed = '')
    {
        $extraWhere = array_merge($this->scopeQuery, $extraWhere);

        return $this->model->getListByPrimaryKeys($primaryKeys, $extraWhere, $trashed);
    }

    /**
     * 删除多条数据
     *
     * @param array $primaryKeys
     * @param array $extraWhere
     * @param string $deleteMethod -- delete / forceDelete
     * @return bool
     */
    public function destroyRepoByPrimaryKeys($primaryKeys, array $extraWhere = [], $deleteMethod = 'delete')
    {
        $extraWhere = array_merge($this->scopeQuery, $extraWhere);

        return $this->model->destroyByPrimaryKeys($primaryKeys, $extraWhere, $deleteMethod);
    }

    /**
     * 自定义分页，在获取所有主键ID之后进行的分页
     *
     * @param array $allDataList
     * @param $pageSize
     * @return array
     */
    public function selfPaginate($allDataList, $pageSize)
    {
        // 数据总条数
        $total = count($allDataList);

        // 当前页数
        $currentPage = request()->get('page');
        $currentPage = empty($currentPage) ? 1 : $currentPage;

        // 每页数据条数
        $pageSize = (empty($pageSize) || intval($pageSize) < 1) ? 15 : intval($pageSize);

        // 当前页数据列表
        $items = array_slice($allDataList, ($currentPage - 1) * $pageSize, $pageSize);

        // 获取分页数据
        $options = [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => 'page'
        ];
        $result = new LengthAwarePaginator(
            $items, $total, $pageSize, $currentPage, $options
        );
        $result = $result->appends(request()->input())->toArray();

        return $result;
    }
}
