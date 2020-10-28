<?php
namespace Lyndon\Repository\Eloquent;


use Lyndon\Model\BaseModel;
use Illuminate\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Trait RepositoryMethodsTrait
 * @package Lyndon\Repository\Eloquent
 *
 * @property BaseModel $model
 */
trait RepositoryMethodsTrait
{
    /**
     * 全局范围查询
     *
     * @var array
     */
    protected $scopeQuery = [];

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
