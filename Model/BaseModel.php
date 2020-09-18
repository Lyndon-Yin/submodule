<?php
namespace Lyndon\Model;


use Illuminate\Database\Eloquent\Model;
use Lyndon\Constant\CodeType;
use Lyndon\Exceptions\ModelException;

/**
 * Class BaseModel
 * @package Lyndon\Model
 */
class BaseModel extends Model
{
    /**
     * 数据表字段
     *
     * @var array
     */
    public $tableColumn = [];

    /**
     * 数据表字段默认值
     *
     * @var array
     */
    public $tableColumnDefaultValue = [];

    /**
     * 添加数据行
     *
     * @param $param
     * @return mixed
     * @throws ModelException
     */
    public function addRow($param)
    {
        $data = [];

        // 过滤表字段
        $this->filterTableColumn();

        foreach ($this->tableColumn as $column => $cast) {
            if (empty($param[$column])) {
                if (isset($this->tableColumnDefaultValue[$column])) {
                    $data[$column] = $this->tableColumnDefaultValue[$column];
                } else {
                    $data[$column] = $this->castDefaultColumn($cast);
                }
            } else {
                $data[$column] = trim($param[$column]);
            }
        }

        if (empty($data)) {
            throw new ModelException('数据库无有效字段', CodeType::MODEL_EMPTY_ADD);
        } else {
            return $this->create($data)->toArray();
        }
    }

    /**
     * 编辑数据行
     *
     * @param mixed $primaryKey
     * @param array $param
     * @param array $extraWhere
     * @return mixed
     * @throws ModelException
     */
    public function editRow($primaryKey, $param, $extraWhere = [])
    {
        $data = [];

        $this->filterTableColumn();

        foreach ($this->tableColumn as $column => $cast) {
            if (isset($param[$column])) {
                $data[$column] = trim($param[$column]);
            }
        }

        if (empty($data)) {
            throw new ModelException('数据库无有效字段', CodeType::MODEL_EMPTY_EDIT);
        } else {
            $where = array_merge(
                [$this->primaryKey => $primaryKey],
                $extraWhere
            );
            return $this->where($where)->update($data);
        }
    }

    /**
     * 获取一行数据
     *
     * @param mixed $primaryKey
     * @param array $extraWhere
     * @param string $trashed -- onlyTrashed / withTrashed
     * @return array
     */
    public function getRowByPrimaryKey($primaryKey, $extraWhere = [], $trashed = '')
    {
        $where = array_merge(
            [$this->primaryKey => $primaryKey],
            $extraWhere
        );

        $info = $this
            ->where($where)
            ->when(! empty($trashed), function ($query) use ($trashed) {
                return $query->$trashed();
            })
            ->first();

        return is_null($info) ? [] : $info->toArray();
    }

    /**
     * 验证一行数据存在性
     *
     * @param mixed $primaryKey
     * @param array $extraWhere
     * @param string $trashed -- onlyTrashed / withTrashed
     * @return bool
     */
    public function existsRowByPrimaryKey($primaryKey, $extraWhere = [], $trashed = '')
    {
        $where = array_merge(
            [$this->primaryKey => $primaryKey],
            $extraWhere
        );

        $info = $this
            ->select($this->primaryKey)
            ->where($where)
            ->when(! empty($trashed), function ($query) use ($trashed) {
                return $query->$trashed();
            })
            ->first();

        return ! is_null($info);
    }

    /**
     * 获取多行数据
     *
     * @param mixed $primaryKeys
     * @param array $extraWhere
     * @param string $trashed -- onlyTrashed / withTrashed
     * @return array
     */
    public function getListByPrimaryKeys($primaryKeys, $extraWhere = [], $trashed = '')
    {
        $result = [];

        $primaryKeys = array_chunk($primaryKeys, 200);

        foreach ($primaryKeys as $chunk) {
            $temp = $this
                ->whereIn($this->primaryKey, $chunk)
                ->when(! empty($extraWhere), function ($query) use ($extraWhere) {
                    return $query->where($extraWhere);
                })
                ->when(! empty($trashed), function ($query) use ($trashed) {
                    return $query->$trashed();
                })
                ->get()->toArray();
            $temp = array_column($temp, null, $this->primaryKey);

            // 根据主键传入顺序排列输出
            foreach ($chunk as $primaryKey) {
                if (isset($temp[$primaryKey])) {
                    $result[] = $temp[$primaryKey];
                }
            }
        }

        return $result;
    }

    /**
     * 删除多条数据
     *
     * @param mixed $primaryKeys
     * @param array $extraWhere
     * @param string $deleteMethod
     * @return bool
     */
    public function destroyByPrimaryKeys($primaryKeys, $extraWhere = [], $deleteMethod = 'delete')
    {
        $primaryKeys = array_chunk($primaryKeys, 200);

        foreach ($primaryKeys as $chunk) {
            $this
                ->whereIn($this->primaryKey, $chunk)
                ->when(! empty($extraWhere), function ($query) use ($extraWhere) {
                    return $query->where($extraWhere);
                })
                ->$deleteMethod();
        }

        return true;
    }

    /**
     * 过滤掉不需要传参的表字段
     */
    private function filterTableColumn()
    {
        foreach ($this->guarded as $val) {
            unset($this->tableColumn[$val]);
        }

        $filterColumn = [
            'created_at',
            'updated_at',
            'deleted_at'
        ];
        foreach ($filterColumn as $val) {
            unset($this->tableColumn[$val]);
        }
    }

    /**
     * 返回不同类型的默认值
     *
     * @param string $cast
     * @return float|int|string
     */
    private function castDefaultColumn($cast)
    {
        $result = '';

        switch ($cast) {
            case 'int':
                $result = 0;
                break;
            case 'float':
            case 'double':
                $result = 0.00;
                break;
            case 'string':
            default:
                break;
        }

        return $result;
    }
}
