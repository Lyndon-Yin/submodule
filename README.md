# 子模块
1，需要更改/config/database.php文件，connections中添加public-mysql公共数据库配置信息

## 跨服务接口调用

首先在/config/database.php文件connections中添加public-mysql公共数据库配置信息

使用方法：  
1，创建一个继承\Lyndon\CurlApi\BaseCurlApi的类，例如GoodsApi  
2，必填参数参数$arriveName，表示你需要调用的应用服务名称  
3，实例化创建的api类，$apiObj = GoodsApi::getInstance();  
```php
class GoodsApi extends \Lyndon\CurlApi\BaseCurlApi
{
    protected $arriveName = 'goods';
}
```
\Lyndon\CurlApi\BaseCurlApi中有get，post，delete等请求方法  
```php
/**
 * 第一个参数，接口地址（不需要传域名）
 * 第二个参数，接口参数（数组形式）
 */
$apiObj->get('shop-goods/goods/goods-info', ['goods_id' => 1]);
$apiObj->post('shop-goods/goods/goods-add', ['goods_name' => '商品名称', 'sell_price' => '499']);
$apiObj->delete('shop-goods/goods/goods-destroy', ['goods_ids' => [1,2,3]]);
```

也可以进行二次封装（强烈推荐用法）
```php
class GoodsApi extends \Lyndon\CurlApi\BaseCurlApi
{
    protected $arriveName = 'goods';

    /**
     * 获取商品详解接口
     *
     * @param array $param
     * @return mixed
     */
    public function getGoodsInfo($param)
    {
       return $this->get('shop-goods/goods/goods-info', $param)
    }
}
```
然后如上实例化对象：$apiObj = GoodsApi::getInstance()，再进行自定义方法调用$apiObj->getGoodsInfo(['goods_id' => 1])。这样可以实现接口复用。

一般同一个应用服务的接口调用写在一个类中（如果接口调用很多，可以拆分成多个类，也是没有问题的），这样，每个应用服务的调用就会有至少一个类。

## 日志

此日志记录采用的是文件形式，存储位置为/storage/logs/*.log

```php
use Lyndon\Log;

/**
 * 一共4种级别的日志记录，分别是info，notice，warning，error
 * 使用时需要根据具体情况选择
 *
 * filename()的参数是文件名称，实际的文件名称会加上日志级别，如file1-info，file4-error
 * info()，notice()，warning()，error()第一个参数是日志搜索的关键字，第二个参数$data可以是数组或字符串类型
 */
Log::filename('file1')->info('search message', $array);
Log::filename('file2')->notice('search message', $array);
Log::filename('file3')->warning('search message', $array);
Log::filename('file4')->error('search message', $array);
```

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
