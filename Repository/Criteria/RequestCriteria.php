<?php
namespace Lyndon\Repository\Criteria;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Lyndon\Repository\Contracts\CriteriaInterface;
use Lyndon\Repository\Contracts\RepositoryInterface;

/**
 * Class RequestCriteria
 * @package Lyndon\Repository\Criteria
 */
class RequestCriteria implements CriteriaInterface
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var RepositoryInterface
     */
    protected $repository;

    /**
     * @var Model
     */
    protected $model;

    /**
     * @var string
     */
    protected $tableName = '';

    /**
     * RequestCriteria constructor
     *
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Apply criteria in query repository
     *
     * @param                     $model
     * @param RepositoryInterface $repository
     *
     * @return mixed
     */
    public function apply($model, RepositoryInterface $repository)
    {
        $this->model      = $model;
        $this->repository = $repository;

        $this->addWhere();
        $this->addOrderBy();

        return $this->model;
    }

    /**
     * 添加where条件
     */
    protected function addWhere()
    {
        $fieldsSearchable = $this->repository->getFieldsSearchable();
        $aliasFieldSearchable = $this->repository->getAliasFieldsSearchable();

        $search       = $this->request->get('search', null);
        $searchFields = $this->request->get('searchFields', null);
        $searchJoin   = $this->request->get('searchJoin', null);

        if (! empty($search) && is_array($fieldsSearchable) && ! empty($fieldsSearchable)) {
            $fieldsSearchable = format_fields_searchable($fieldsSearchable);

            $search       = $this->parserSearchData($search, $fieldsSearchable, $aliasFieldSearchable);
            $searchFields = $this->parserSearchFields($searchFields, $fieldsSearchable, $aliasFieldSearchable);

            $modelForceAndWhere = strtolower($searchJoin) !== 'or';
            $isFirstField       = true;

            $this->model = $this->model->where(function ($query) use ($search, $searchFields, $modelForceAndWhere, $isFirstField) {
                // 链表查询的where条件集合
                $relationWhere = [];

                foreach ($searchFields as $field => $condition) {
                    if (! isset($search[$field])) {
                        continue;
                    }
                    $searchValue = $search[$field];

                    // 分离出链表查询字段
                    if (strpos($field, '.') === false) {
                        $relationTable = false;
                    } else {
                        $explode  = explode('.', $field);

                        $field = array_pop($explode);
                        $relationTable = implode('.', $explode);
                    }

                    $searchValue = $this->parserWhereValue($searchValue, $condition);
                    if ($searchValue === '') {
                        continue;
                    }

                    if ($relationTable === false) {
                        if ($isFirstField) {
                            $query = $this->parserCondition($query, $field, $condition, $searchValue, true, $this->getTableName());

                            $isFirstField = false;
                        } else {
                            $query = $this->parserCondition($query, $field, $condition, $searchValue, $modelForceAndWhere, $this->getTableName());
                        }
                    } else {
                        $relationWhere[$relationTable][] = ['field' => $field, 'condition' => $condition, 'value' => $searchValue];
                    }
                }

                // 进行链表查询
                // 之所以没在上面的循环中直接查询，是为了避免对同一张表多次关联
                foreach ($relationWhere as $relationTable => $chunk) {
                    if ($isFirstField) {
                        $query = $query->whereHas($relationTable, function ($query) use ($chunk, $modelForceAndWhere) {
                            foreach ($chunk as $key => $val) {
                                $isAndJoin = ($key === 0 || $modelForceAndWhere);

                                $query = $this->parserCondition($query, $val['field'], $val['condition'], $val['value'], $isAndJoin);
                            }
                            return $query;
                        });

                        $isFirstField = false;
                    } elseif ($modelForceAndWhere) {
                        $query = $query->whereHas($relationTable, function ($query) use ($chunk) {
                            foreach ($chunk as $key => $val) {
                                $query = $this->parserCondition($query, $val['field'], $val['condition'], $val['value'], true);
                            }
                            return $query;
                        });
                    } else {
                        $query = $query->orWhereHas($relationTable, function ($query) use ($chunk) {
                            foreach ($chunk as $key => $val) {
                                $isAndJoin = ($key === 0);

                                $query = $this->parserCondition($query, $val['field'], $val['condition'], $val['value'], $isAndJoin);
                            }
                            return $query;
                        });
                    }
                }

                return $query;
            });
        }
    }

    /**
     * 添加orderBy条件
     */
    protected function addOrderBy()
    {
        $orderBy = $this->request->get('orderBy', null);
        if (empty($orderBy)) {
            $defaultOrderByFields = $this->repository->defaultOrderByFields();

            if (empty($defaultOrderByFields)) {
                $orderBy = [$this->getTableName() . '.id' => 'asc'];
            } else {
                $orderBy = [];
                foreach ($defaultOrderByFields as $sortColumn => $sortedBy) {
                    if (strpos($sortColumn, '.') === false) {
                        $orderBy[$this->getTableName() . '.' . $sortColumn] = $sortedBy;
                    } else {
                        $orderBy[$sortColumn] = $sortedBy;
                    }
                }
            }
        } else {
            $aliasOrderByFields = $this->repository->getAliasOrderByFields();

            $orderBy = $this->parserOrderBy($orderBy, $aliasOrderByFields);
        }

        // 链表排序，存在重复leftJoin同一个表
        // 链表表名暂存，进行重复性验证
        $joinTableRepeat = [];

        foreach ($orderBy as $sortColumn => $sortedBy) {
            $split = explode('|', $sortColumn, 2);

            if (count($split) > 1) {
                // 形如：LeftJoinTable|LeftJoinTable.sortColumn::desc
                $sortTable  = $split[0];
                $sortColumn = $split[1];

                if (! in_array($sortTable, $joinTableRepeat)) {
                    // 链表排序，关联关系暂存
                    $joinTableRepeat[] = $sortTable;

                    $split = explode(':', $sortTable, 2);
                    if (count($split) > 1) {
                        $sortTable = $split[0];
                        $keyName = $this->getTableName() . '.' . $split[1];
                    } else {
                        $keyName = $this->getTableName() . '.' . $sortTable . '_id';
                    }

                    $this->model = $this->model
                        ->leftJoin($sortTable, $keyName, '=', $sortTable . '.id')
                        ->orderBy($sortColumn, $sortedBy)
                        ->addSelect($this->getTableName() . '.*');
                } else {
                    // 此表已经leftJoin，直接orderBy即可
                    $this->model = $this->model->orderBy($sortColumn, $sortedBy);
                }
            } else {
                $this->model = $this->model->orderBy($sortColumn, $sortedBy);
            }
        }
    }

    /**
     * 解析$orderBy参数
     *
     * 形如：
     * $orderBy = "age::asc;name::desc"
     * 结果：
     * [
     *    "age" => "asc",
     *    "name" => "desc"
     * ]
     *
     * @param $orderBy
     * @param $aliasOrderByFields
     * @return array
     */
    protected function parserOrderBy($orderBy, array $aliasOrderByFields = [])
    {
        $result = [];

        // 前端传参字符串转换为数组形式
        $orderBy = parser_order_by($orderBy);

        // 是否存在主键排序，不存在的情况下默认加入主键排序
        $hasPrimaryKeySort = false;

        // 当前表的主键字段
        $primaryKey = $this->getTableName() . '.id';

        foreach ($orderBy as $column => $sortedBy) {
            // 排序别名转换成标准名称
            if (isset($aliasOrderByFields[$column])) {
                $column = trim($aliasOrderByFields[$column]);
            }

            // 排序字段添加表名约束
            if (strpos($column, '.') === false) {
                $column = $this->getTableName() . '.' . $column;
            }

            // 验证是否有主键排序
            if ($primaryKey === $column) {
                $hasPrimaryKeySort = true;
            }

            $result[$column] = $sortedBy;
        }

        // 默认加入主键正序排序
        if ($hasPrimaryKeySort === false) {
            $result[$primaryKey] = 'asc';
        }

        return $result;
    }

    /**
     * 解析$search参数
     *
     * 形如：
     * $search = "name::lyndon;age::18"
     * 结果：
     * [
     *    "name" => "lyndon"
     *    "age" => "18"
     * ]
     *
     * @param mixed $search
     * @param array $fieldsSearchable
     * @param array $aliasFieldSearchable
     * @return array
     */
    protected function parserSearchData($search, array $fieldsSearchable = [], array $aliasFieldSearchable = [])
    {
        $result = [];

        // 前端传参字符串转换为数组形式
        $search = parser_search_data($search);

        // 过滤非法参数
        foreach ($search as $field => $value) {
            if (isset($aliasFieldSearchable[$field])) {
                $field = trim($aliasFieldSearchable[$field]);
            }

            if (isset($fieldsSearchable[$field])) {
                $result[$field] = $value;
            }
        }

        return $result;
    }

    /**
     * 解析$searchFields参数
     * 重置$fieldsSearchable中的查询条件condition
     *
     * 形如：
     * $searchFields = "name::like;birth::between;age::!="
     * 返回结果：
     * [
     *    "name" => "like",
     *    "birth" => "between",
     *    "age" => "!="
     * ]
     *
     * @param mixed $searchFields
     * @param array $fieldsSearchable
     * @param array $aliasFieldSearchable
     * @return array
     */
    protected function parserSearchFields($searchFields, array $fieldsSearchable = [], array $aliasFieldSearchable = [])
    {
        $searchFields = (is_array($searchFields) || is_null($searchFields)) ? $searchFields : explode(';', $searchFields);

        // 用户自定义的搜索条件替换默认搜索条件
        if (! empty($searchFields)) {
            $acceptedConditions = ['=', '>=', '>', '<=', '<', '!=', '<>', 'in', 'notin', 'between', 'like'];

            foreach ($searchFields as $field) {
                $field_parts = explode('::', $field);

                if (count($field_parts) !== 2) {
                    continue;
                }

                $field_parts[0] = trim($field_parts[0]);
                $field_parts[1] = trim($field_parts[1]);

                if (empty($field_parts[0]) || empty($field_parts[1])) {
                    continue;
                }
                if (! in_array($field_parts[1], $acceptedConditions)) {
                    continue;
                }

                // 重置$fieldsSearchable默认condition
                if (isset($aliasFieldSearchable[$field_parts[0]])) {
                    $field_parts[0] = trim($aliasFieldSearchable[$field_parts[0]]);
                }
                if (isset($fieldsSearchable[$field_parts[0]])) {
                    $fieldsSearchable[$field_parts[0]] = strtolower($field_parts[1]);
                }
            }
        }

        return $fieldsSearchable;
    }

    /**
     * 生成where查询
     *
     * @param $query
     * @param $field
     * @param $condition
     * @param $value
     * @param $isAndJoin
     * @param $tableName
     * @return mixed
     */
    protected function parserCondition($query, $field, $condition, $value, $isAndJoin, $tableName = '')
    {
        if (! empty($tableName)) {
            $field = $tableName . '.' . $field;
        }

        switch ($condition) {
            case 'in':
                $whereType = $isAndJoin ? 'whereIn' : 'orWhereIn';
                $query->$whereType($field, $value);
                break;
            case 'notin':
                $whereType = $isAndJoin ? 'whereNotIn' : 'orWhereNotIn';
                $query->$whereType($field, $value);
                break;
            case 'between':
                if (! is_array($value) || ! isset($value[0]) || ! isset($value[1])) {
                    break;
                }
                if ($value[0] !== '' && $value[1] !== '') {
                    $whereType = $isAndJoin ? 'whereBetween' : 'orWhereBetween';
                    $query->$whereType($field, $value);
                } elseif ($value[0] !== '') {
                    $whereType = $isAndJoin ? 'where' : 'orWhere';
                    $query->$whereType($field, '>=', $value[0]);
                } elseif ($value[1] !== '') {
                    $whereType = $isAndJoin ? 'where' : 'orWhere';
                    $query->$whereType($field, '<=', $value[1]);
                }
                break;
            default:
                $whereType = $isAndJoin ? 'where' : 'orWhere';
                $query->$whereType($field, $condition, $value);
                break;
        }

        return $query;
    }

    /**
     * 生成where查询中value值
     *
     * @param $value
     * @param $condition
     * @return array|string
     */
    protected function parserWhereValue($value, $condition)
    {
        $result = '';

        $value = trim($value);
        if ($value === '') {
            return $result;
        }

        switch ($condition) {
            case 'in':
            case 'notin':
                $result = explode(',', $value);
                break;
            case 'like':
                $result = '%' . $value . '%';
                break;
            case 'between':
                if (strpos($value, '~') !== false) {
                    $result = explode('~', $value, 2);
                } elseif (strpos($value, ',') !== false) {
                    $result = explode(',', $value, 2);
                } else {
                    $result = [$value];
                }

                if (! isset($result[0])) {
                    $result[0] = '';
                } elseif (strtotime($result[0]) !== false && strpos($result[0], ':') == false) {
                    $result[0] = trim($result[0]) . ' 00:00:00';
                } else {
                    $result[0] = trim($result[0]);
                }
                if (! isset($result[1])) {
                    $result[1] = '';
                } elseif (strtotime($result[1]) !== false && strpos($result[1], ':') == false) {
                    $result[1] = trim($result[1]) . ' 23:59:59';
                } else {
                    $result[1] = trim($result[1]);
                }
                break;
            default:
                $result = trim($value);
        }

        return $result;
    }

    /**
     * 获取表名称
     *
     * @return mixed
     */
    protected function getTableName()
    {
        if (empty($this->tableName)) {
            $this->tableName = $this->model->getModel()->getTable();
        }

        return $this->tableName;
    }
}
