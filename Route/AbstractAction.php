<?php
namespace Lyndon\Route;


use Illuminate\Http\Request;

/**
 * Class AbstractAction
 * @package Lyndon\Route
 */
abstract class AbstractAction
{
    const TAG = 'AbstractAction';

    /**
     * 请求方式：GET
     */
    const METHOD_GET = 'GET';

    /**
     * 请求方式：POST
     */
    const METHOD_POST = 'POST';

    /**
     * 请求方式：PUT
     */
    const METHOD_PUT = 'PUT';

    /**
     * 请求方式：DELETE
     */
    const METHOD_DELETE = 'DELETE';

    /**
     * 请求方式：OPTIONS
     */
    const METHOD_OPTIONS = 'OPTIONS';

    /**
     * 请求方式：HEAD
     */
    const METHOD_HEAD = 'HEAD';

    /**
     * 请求方式：PATCH
     */
    const METHOD_PATCH = 'PATCH';

    /**
     * 请求方式：TRACE
     */
    const METHOD_TRACE = 'TRACE';

    /**
     * 请求方式：CONNECT
     */
    const METHOD_CONNECT = 'CONNECT';

    /**
     * @var array 所有的请求方式
     */
    private $methods = [
        self::METHOD_GET,
        self::METHOD_POST,
        self::METHOD_PUT,
        self::METHOD_DELETE,
        self::METHOD_OPTIONS,
        self::METHOD_HEAD,
        self::METHOD_PATCH,
        self::METHOD_TRACE,
        self::METHOD_CONNECT
    ];

    /**
     * @var string Action类名
     */
    private $name = '';

    /**
     * 前置方法返回结果
     *
     * @var null|mixed
     */
    public $preReturn = null;

    /**
     * 主方法返回结果
     *
     * @var null|mixed
     */
    public $onReturn = null;

    /**
     * AbstractAction constructor.
     */
    public function __construct()
    {
        $this->name = get_called_class();

        $this->onInit();
    }

    /**
     * 子类调用此方法作为构造方法
     */
    protected function onInit()
    {

    }

    /**
     * onRun()前置操作方法
     *
     * @param Request $request
     */
    public function preRun(Request $request)
    {

    }

    /**
     * 主方法
     * 可以通过$this->preReturn获取preRun()的返回结果
     *
     * @param Request $request
     * @return mixed
     */
    public abstract function onRun(Request $request);

    /**
     * onRun()后置操作方法
     * 可以通过$this->onReturn和$this->preReturn获取前两个方法的返回结果
     * 不重写该方法，整个action将返回onRun()返回的结果，若重写，则返回当前方法的结果
     *
     * @param Request $request
     * @return mixed
     */
    public function postRun(Request $request)
    {
        return $this->onReturn;
    }

    /**
     * 所有的请求方式
     *
     * @return array
     */
    public function getMethods()
    {
        return $this->methods;
    }

    /**
     * 获取子类Action类名
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * 当前Action允许的请求方式
     *
     * @return string
     */
    public abstract function allowMethod();

    /**
     * 当前Action允许的请求方式列表
     *
     * @return array
     */
    public function allowMethods()
    {
        return [];
    }
}
