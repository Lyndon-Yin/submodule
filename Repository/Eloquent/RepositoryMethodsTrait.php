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
    use RepositoryCacheTrait;

    /**
     * 全局范围查询
     *
     * @var array
     */
    protected $scopeQuery = [];

    /**
     * 是否应用缓存服务
     *
     * @var bool
     */
    protected $applyCache = false;

    /**
     * 缓存过期时间，默认10天
     *
     * @var int
     */
    protected $cacheExpire = 864000;

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

        // 数据库编辑
        $info = $this->model->editRow($primaryKey, $param, $extraWhere);

        // 缓存数据删除
        if ($this->applyCache) {
            $this->forgetRepoRowCache($this->getRepoCacheKey($primaryKey));
            $this->forgetRepoRowCache($this->getTrashedRepoCacheKey($primaryKey));
        }

        return $info;
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

        // 不应用缓存，直接查询数据库并返回结果
        if (! $this->applyCache) {
            return $this->model->getRowByPrimaryKey($primaryKey, $extraWhere, $trashed);
        }

        if (empty($trashed)) {
            $cacheKey = $this->getRepoCacheKey($primaryKey);

            // 获取缓存数据
            $info = $this->getRepoRowCache($cacheKey);

            if (is_null($info)) {
                // 无缓存数据，数据库获取
                $info = $this->model->getRowByPrimaryKey($primaryKey);
                // 查询结果存入缓存
                $this->putRepoRowCache($cacheKey, $info);
            }

            return $this->filterRepoRowCache($info, $extraWhere);
        } else {
            $cacheKey = $this->getTrashedRepoCacheKey($primaryKey);

            // 获取缓存数据
            $info = $this->getRepoRowCache($cacheKey);

            if (is_null($info)) {
                // 无缓存数据，数据库获取
                $info = $this->model->getRowByPrimaryKey($primaryKey, [], 'withTrashed');
                // 查询结果存入缓存
                $this->putRepoRowCache($cacheKey, $info);
            }

            if ($trashed == 'onlyTrashed' && empty($info['deleted_at'])) {
                return [];
            }

            return $this->filterRepoRowCache($info, $extraWhere);
        }
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

        // 不应用缓存，直接查询数据库并返回结果
        if (! $this->applyCache) {
            $results = $this->model->getListByPrimaryKeys($primaryKeys, $extraWhere, $trashed);

            return array_values($results);
        }

        $results = [];
        if (empty($trashed)) {
            // 缓存中获取数据，不在缓存中的主键分离出来
            $queryPrimaryKeys = [];
            foreach ($primaryKeys as $val) {
                $tmp = $this->getRepoRowCache($this->getRepoCacheKey($val));

                if (is_null($tmp)) {
                    $queryPrimaryKeys[] = $val;

                    $results[$val] = [];
                } elseif (! empty($tmp)) {
                    $results[$val] = $tmp;
                }
            }

            // 未在缓存中的主键，查询数据库
            $tmp = $this->model->getListByPrimaryKeys($queryPrimaryKeys);
            foreach ($queryPrimaryKeys as $val) {
                if (isset($tmp[$val])) {
                    $results[$val] = $tmp[$val];
                    $this->putRepoRowCache($this->getRepoCacheKey($val), $tmp[$val]);
                } else {
                    $this->putRepoRowCache($this->getRepoCacheKey($val), []);
                }
            }
            unset($tmp, $queryPrimaryKeys);

            $results = array_filter(array_values($results));
        } else {
            // 缓存中获取数据，不在缓存中的主键分离出来
            $queryPrimaryKeys = [];
            foreach ($primaryKeys as $val) {
                $tmp = $this->getRepoRowCache($this->getTrashedRepoCacheKey($val));

                if (is_null($tmp)) {
                    $queryPrimaryKeys[] = $val;

                    $results[$val] = [];
                } elseif (! empty($tmp)) {
                    $results[$val] = $tmp;
                }
            }

            // 未在缓存中的主键，查询数据库
            $tmp = $this->model->getListByPrimaryKeys($queryPrimaryKeys, [], 'withTrashed');
            foreach ($queryPrimaryKeys as $val) {
                if (isset($tmp[$val])) {
                    $results[$val] = $tmp[$val];
                    $this->putRepoRowCache($this->getTrashedRepoCacheKey($val), $tmp[$val]);
                } else {
                    $this->putRepoRowCache($this->getTrashedRepoCacheKey($val), []);
                }
            }
            unset($tmp, $queryPrimaryKeys);

            $results = array_filter(array_values($results));
            if ($trashed == 'onlyTrashed') {
                $results = collect($results)->whereNotNull('deleted_at')->toArray();
            }
        }

        return $this->filterRepoListCache($results, $extraWhere);
    }

    /**
     * 验证多行数据存在性
     *
     * @param array $primaryKeys
     * @param array $extraWhere
     * @param string $trashed
     * @return array
     */
    public function existsRepoListByPrimaryKeys($primaryKeys, $extraWhere = [], $trashed = '')
    {
        $extraWhere = array_merge($this->scopeQuery, $extraWhere);

        return $this->model->existsListByPrimaryKeys($primaryKeys, $extraWhere, $trashed);
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

        // 删除数据库
        $result = $this->model->destroyByPrimaryKeys($primaryKeys, $extraWhere, $deleteMethod);

        // 删除缓存，$extraWhere不为空存在多删的情况
        if ($this->applyCache) {
            foreach ($primaryKeys as $val) {
                $this->forgetRepoRowCache($this->getRepoCacheKey($val));
                $this->forgetRepoRowCache($this->getTrashedRepoCacheKey($val));
            }
        }

        return $result;
    }

    /**
     * 根据用户传参获取主键列表
     *
     * @param int $maxLimit
     * @param string $trashed
     * @return mixed
     */
    public function getAllIdsByCriteria($maxLimit = 1000, $trashed = '')
    {
        $primaryKey = $this->model->getPrimaryKeyField();

        // repository应用范围查询和标准查询
        $this->applyScopeQuery()->applyCriteria();

        // 数据查询
        $result = $this->model
            ->when($maxLimit > 0, function ($query) use ($maxLimit) {
                return $query->limit($maxLimit);
            })
            ->when(! empty($trashed), function ($query) use ($trashed) {
                return $query->$trashed();
            })
            ->pluck($primaryKey)->toArray();

        // 重置模型
        $this->resetModel();

        return $result;
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
