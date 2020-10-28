# 子模块

## 日志

## 模型

自定义模型必须继承自Lyndon\Model\BaseModel，该基础模型继承自Illuminate\Database\Eloquent\Model，所以laravel原模型属性都可用

提供以下几个属性变量：
```php
/**
 * 数据库字段列表
 *
 * 以下字段会被忽略，不用传入：
 * 1，created_at，updated_at，deleted_at
 * 2，定义在模型属性guarded中的字段
 *
 * 类型限定，包含以下几个：
 * int：   整型，默认值为0
 * float： 数字型，默认值是0.00
 * string：字符串，默认值是''（空字符串）,
 * null：  null
 */
$tableColumn = [
  'name'       => 'string',
  'age'        => 'int',
  'created_at' => 'null'
];

/**
 * 数据库字段默认值
 *
 * $tableColumn是数据库字段类型限定，调用addRow（下面会介绍）方法后，如果字段未传，会用类型的默认值
 * 如果想重新定义未传值的默认值，可以在此定义，此字段和建数据表时，'default'语句类似
 */
$tableColumnDefaultValue = [
  'sale_status' => 'off'
];
```

提供以下几个属性方法：  
```php
/**
 * 添加一行记录，$param参数不需要过滤掉非数据库字段，
 * 该方法会根据$tableColumn属性自动过滤，并且可以根据类型限定自动填充默认值
 */
$this->addRow($param);

/**
 * 根据主键编辑一行记录
 * $param中存在的字段才会更新，也是不需要过滤掉非数据库字段，对于未传入的数据库字段，不会用默认值覆盖，这点同addRow有区别
 * $extraWhere允许传入额外的限定字段，例如商家id做安全限定，防止用户随意输入了一个id，更新了别的商家数据（如果前期验证不充分的话）
 */
$this->editRow($primaryKey, $param, $extraWhere = []);

/**
 * 根据主键查询一条数据
 * $extraWhere同上
 * $trashed支持软删数据查询，可以传入onlyTrashed或者withTrashed
 */
$this->getRowByPrimaryKey($primaryKey, $extraWhere = [], $trashed = '')

/**
 * 根据主键列表查询多条数据
 */
$this->getListByPrimaryKeys($primaryKeys, $extraWhere = [], $trashed = '')

/**
 * 验证一行数据存在性
 */
$this->existsRowByPrimaryKey($primaryKey, $extraWhere = [], $trashed = '');

/**
 * 根据主键列表删除多条数据
 * $deleteMethod默认使用delete方法，若扩展了软删，可能需要用到forceDelete方法
 */
$this->destroyByPrimaryKeys($primaryKeys, $extraWhere = [], $deleteMethod = 'delete');
```

## 锁

## repository仓库

## token验证

## 表单验证
