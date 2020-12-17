# 子模块
1，composer require curl/curl ^2.3

2，需要更改/config/database.php文件，connections中添加public-mysql公共数据库配置信息

3，需要更改/config/database.php文件，redis中添加distributedLock分布式锁配置信息

4，需要更改/config/database.php文件，redis中添加optimisticLock分布式锁配置信息

5，composer require firebase/php-jwt ^5.2

## 跨服务接口调用

依赖curl/curl三方库

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

#### 分布式锁
基于redis的分布式锁，所以需要支持redis

首先在/config/database.php文件redis中添加distributedLock分布式锁配置信息
```php
// 分布式锁
'distributedLock' => [
    'url'      => env('REDIS_DISTRIBUTE_URL'),
    'host'     => env('REDIS_DISTRIBUTE_HOST', '127.0.0.1'),
    'password' => env('REDIS_DISTRIBUTE_PASSWORD', null),
    'port'     => env('REDIS_DISTRIBUTE_PORT', '6379'),
    'database' => env('REDIS_DISTRIBUTE_DB', '1'),
],
```

#### 乐观锁
基于redis的乐观锁，所以需要支持redis

首先在/config/database.php文件redis中添加optimisticLock分布式锁配置信息
```php
// 乐观锁
'optimisticLock' => [
    'url'      => env('REDIS_OPTIMISTIC_URL'),
    'host'     => env('REDIS_OPTIMISTIC_HOST', '127.0.0.1'),
    'password' => env('REDIS_OPTIMISTIC_PASSWORD', null),
    'port'     => env('REDIS_OPTIMISTIC_PORT', '6379'),
    'database' => env('REDIS_OPTIMISTIC_DB', '1'),
],
```

## repository仓库
Laravel仓库层，介于model和service之间的数据查询层。在l5-repository基础上做了删减和优化。

### 使用

#### 创建一个Repository

```php
namespace App\Repository;

use Lyndon\Repository\Eloquent\BaseRepository;
use Lyndon\Repository\Criteria\RequestCriteria;

class UserRepository extends BaseRepository
{
    /**
     * 前端允许传参的搜索列表
     *
     * 形如：
     * ?search=name::lyndon;age::18
     * 生成的sql如下：
     * where name like "%lyndon%" and age="18"
     *
     * 可以更改搜索的条件，例如
     * ?search=name::lyndon;age::18&searchFields=name::=;age::!=
     * 生成的sql如下：
     * where name="lyndon" and age!="18"
     *
     * 也可以更改搜索的连接条件，and或者or：
     * ?search=name::lyndon;age::18&searchFields=name::=;age::!=&searchJoin=or
     * 生成的sql如下：
     * where name="lyndon" or age!="18"
     *
     * 更复杂用法，可以根据根据关联关系进行筛选
     * ?search=name::lyndon;post.title::标题
     * Laravel模型层的拼接如下：
     * $model->whereHas('post', function ($query) {
     *     $query->where('title', '=', '标题')
     * })->where('name', 'like', '%lyndon%')
     *
     */
    public $fieldSearchable = [
        "name" => "like",
        "age",  // 默认筛选条件就是等于号"="
        "post.title" => "="
    ];

    /*
     * $fieldSearchable的别名形式，key为别名，value为$fieldSearchable中的元素
     * 一般可以用来对前端隐藏数据库字段名和表的关联查询
     *
     * ?search=name::lyndon;post.title::标题
     * 和
     * ?search=xingming::lyndon;title::标题
     * 生成的sql没有区别
     */
    public $aliasFieldSearchable = [
        "xingming" => "name",
        "title" => "post.title"
    ];

    /**
     * 排序字段的的别名形式，和$aliasFieldSearchable相似
     *
     * 形如：
     * ?orderBy=age::desc;primarKey::asc
     * 生成的sql如下：
     * order by age desc,id asc;
     */
    public aliasOrderByFields = [
        "primarKey" => "id"
    ];

    /**
     * 可以让所有repository继承一个BaseRepository的基类
     * 这样就可以避免所有的repository都写如下的__construct()
     */
    public function __constract()
    {
        parent::__construct(app());
    }

    /**
     * 重写boot()方法
     * 此处加入标准查询，这样就可以通过设置$fieldSearchable，前端按照一定规则传参，数据库实现数据筛选功能
     *
     * 你也可以在每个repository查询方法前再将该查询类push进去，这样，其他方法要使用就需要再push一遍，
     * push不代表会自动应用，还需要在具体方法中手动应用：$this->applyCriteria()，如下面的自定义方法getPostList()
     */
    public function boot()
    {
        $this->pushCriteria(app(RequestCriteria::class));
    }

    /**
     * 当前repository对应的model，必须
     *
     * @return string
     */
    public function model()
    {
        return "App\\Post";
    }

    /**
     * 自定义查询方法
     */
    public function getPostList()
    {
        // repository应用所有pushCriteria查询的查询类
        $this->applyCriteria();

        /*
         * 数据库查询
         * 就这么一个简单语句，前端通过search传参的搜索条件就已经被应用，做到了完全的分离
         */
        return $this->model->limit(20)->get()->toArray()
    }
}
```

#### 创建自定义查询类
```php
namespace App\Criteria;

use Lyndon\Repository\Contracts\CriteriaInterface;
use Lyndon\Repository\Contracts\RepositoryInterface;

class UserCriteria extend CriteriaInterface
{
    public function apply($model, RepositoryInterface $repository)
    {
        // 获取前端传参
        $demo = request()->get('demo', null);

        // 追加查询条件
        $model = $model->when(! empty($demo), function ($query) use ($demo) {
            return $query->where('demo', $demo);
        });

        return $model;
    }
}
```

要想使用自定义的查询类，只要在上述repository中$this->pushCriteria(UserCriteria::class)推入，
可以在boot()中，这样全局查询都可以使用，自然查询前需要$this->applyCriteria()一下，也可以在某个自定义的方法中添加。
当你全局添加了该查询类，某个自定义方法中不想使用了，也可以$this->popCriteria(UserCriteria::class)将该类弹出。

## 表单验证

### 创建一个表单验证
```php
namespace App\Validators;

use Lyndon\Repository\Validators\BaseValidator

class DemoValidator extends BaseValidator
{
    const RULE_NAME = 'name';

    const RULE_ID = 'id';

    protected $rules = [
        self::RULE_NAME => [
            'name' => 'required|max:16'
        ],
        self::RULE_ID => [
            'id' => 'required|integer|min:1'
        ]
    ];

    protected $message = [
        'name.required' => '名称不能为空',
        'name.max'      => '名称超过最大长度',
        'id.required'   => 'ID不能为空',
        'id.integer'    => 'ID必须为整数',
        'id.min'        => 'ID必须是大于0的整数'
    ];
}
```

### 使用创建的表单验证
```php
use App\Validators\DemoValidator;
use Lyndon\Repository\Exceptions\ValidatorException;

class DemoClass
{
    public function demo()
    {
        // new这个验证类
        $validator = new DemoValidator();

        try {
            /*
             * 基本用法：
             * $param是需要验证的索引数组，选择的规则是RULE_NAME
             * 验证失败会抛出一个ValidatorException的异常
             */
            $validator->with($param)->passesOrFail(DemoValidator::RULE_NAME);

            /*
             * 高级用法：
             * passesOrFail方法中可以传入多个规则，如果规则重复了，则以该规则第一次出现的为准，
             * 甚至，可以传入一个数组，将自定义的规则传入，至于自定义的规则错误信息提示，
             * 可以在passesOrFail方法前pushMessage()
             */
             $validator->with($param)
                ->pushMessage([
                    'value.require' => '不能为空'
                ])
                ->passesOrFail(
                     DemoValidator::RULE_NAME,
                     DemoValidator::RULE_ID,
                     [
                        'value' => 'required'
                     ]
                );
        } catch (ValidatorException $e) {
            // getErrorData()获取所有的验证错误信息
            $errorData = $validator->getErrorData();
            return $errorData;
        }
    }
}
```

## Laravel路由

基于Laravel的通用路由解析。只写一次，再也不用每次添加接口都要改动routes/api.php或者routes/web.php了。
目录即访问地址，并且目录层级灵活多变。

### 使用简介

首先routes/api.php或者routes/web.php添加以下代码：
```php
use Illuminate\Http\Request;

Route::group([
    'middleware' => [
        // 中间件列表
    ]
], function ($router) {
    $router->any('{slug?}', function (Request $request) {
        return \Lyndon\Route\Action\Path4Router::route($request);
    })->where('slug', '(.*)?');
});
```

注意：  
App\Providers\RouteServiceProvider文件中，对于api和web有各自的路由支持，分别是mapWebRoutes()和mapApiRoutes()方法。
这两个方法中的prefix()中的'web'和'api'，或者其他自定义的前缀prefix，是上述静态方法\Lyndon\Route\Action\Path4Router::route($request)的第二个参数。

例如，若在routes/api.php文件中，且mapApiRoutes()中有prefix('api')，则：
```php
use Illuminate\Http\Request;

Route::group([
    'middleware' => [
        // 中间件列表
    ]
], function ($router) {
    $router->any('{slug?}', function (Request $request) {
        return \Lyndon\Route\Action\Path4Router::route($request, 'api');
    })->where('slug', '(.*)?');
});
```

然后在app/Http/Controllers中添加如下目录结构：
```
AppType1
├── Module1
│   ├── Controller2
│   │   ├── Action1.php
│   │   ├── Action2.php
│   │   └── Action3.php
│   └── Controller2
├── Module2
AppType2
├── Module1
    ├── Controller1
    │   ├── Action1.php
    │   └── Action2.php
    └── Controller2
```
AppType，Module，Controller均为目录，Action为Class类，其中onRun()方法是具体执行方法。

AppType：接口类型，例如Admin（商家端），Client（用户端）等  
Module：模块，例如Goods（商品模块），Marketing（营销模块）等  
Controller：控制器，例如Brand（品牌控制），Stock（库存控制）等  
Action：方法，例如BrandList（品牌列表方法），BrandCreate（品牌添加方法）等  


注意：  
以上是最大支持的目录层级，共4层，但不表示只支持4层目录结构，当前路由最小支持2层目录结构，仅剩下Controller和Action。
也可以是三层，Module，Controller和Action。更加灵活多变。

接着在app/Http/Controllers中添加一个Brand文件夹，该文件夹下创建一个BrandList.php文件，文件内容如下：
```php
namespace App\Http\Controllers\Brand;


use Illuminate\Http\Request;
use Lyndon\Route\Action\AbstractAction;

class BrandList extends AbstractAction
{
    public function allowMethod()
    {
        return self::METHOD_GET;
    }

    public function onRun(Request $request)
    {
        return 'brandList';
    }
}
```
此时通过地址：http://localhost/brand/brand-list即可访问该方法了。如果是api访问的话，地址可能是：http://localhost/api/brand/brand-list。
这是最简单的2层目录，同样你可以在Brand文件夹外再套一层，例如ShopGoods，此时上述类的namespace变为App\Http\Controllers\ShopGoods\Brand
访问地址也就变成：http://localhost/shop-goods/brand/brand-list。

### config配置
默认路由根目录是App\\Http\\Controllers，可以通过一下配置，更改路由根目录

app/config目录下添加LyndonRoute.php配置文件：
```php
return [
    /*
     * 路由解析根目录，默认是App\Http\Controllers
     * 在这目录下可以创建appType，Module，Controller等目录
     */
    'actionDir' => 'App\\Http\\Controllers',
];
```

