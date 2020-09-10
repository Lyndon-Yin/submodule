<?php
namespace Lyndon\Route;


use Illuminate\Http\Request;
use Lyndon\Exceptions\RouteException;

/**
 * Class Path4Router
 * @package Lyndon\Route
 */
class Path4Router
{
    const TAG = 'Path4Router';

    /**
     * 路由参数个数：包括接口类型
     */
    const SEGMENTS_NUM = 4;

    /**
     * @var string Action目录
     */
    public static $actionDir = 'App\\Http\\Controllers';

    /**
     * 分析路由，并执行Action
     *
     * @param Request $request
     * @param string $prefix
     * @return array|string
     */
    public static function route(Request $request, $prefix = '')
    {
        try {
            // 初始化http路由访问根目录
            self::initActionDir();

            // 生成完整访问类名
            $actionName = self::getActionName($request, $prefix);

            return ActionRunner::run($actionName, $request);
        } catch (\Exception $e) {
            $appEnv = strtolower(env('APP_ENV', 'dev'));
            if (in_array($appEnv, ['dev', 'test'])) {
                // dev和test环境，返回详细错误信息
                return sprintf(
                    "%s in %s file at %s line\r%s",
                    $e->getMessage(),
                    $e->getFile(),
                    $e->getLine(),
                    $e->getTraceAsString()
                );
            } else {
                // beta，pro环境仅返回错误提示，不返回详细信息
                return $e->getMessage();
            }
        }
    }

    /**
     * 生成访问类名
     *
     * @param Request $request
     * @param string $prefix
     * @return string
     * @throws RouteException
     */
    protected static function getActionName(Request $request, $prefix = '')
    {
        // 去除url前缀，并验证前缀的合法性
        $segments = $request->segments();
        if (! empty($prefix)) {
            $urlPrefix = array_shift($segments);
            if ($urlPrefix !== $prefix) {
                throw new RouteException(sprintf(
                    self::TAG . ' route(), the requested prefix "%s" wrong, must be "%s".',
                    $urlPrefix, $prefix
                ));
            }
        }

        $segments = self::analyzeUri($segments);
        $action     = array_pop($segments);
        $controller = array_pop($segments);
        $module     = array_pop($segments);
        $appType    = array_pop($segments);

        return self::actionName($appType, $module, $controller, $action);
    }

    /**
     * 路由地址初始化
     */
    protected static function initActionDir()
    {
        // 根目录初始化
        $actionDir = app('config')->get('LyndonRoute.actionDir');
        if (! is_null($actionDir)) {
            self::$actionDir = $actionDir;
        }
    }

    /**
     * 获取Action类名，包括包名
     *
     * @param string $appType
     * @param string $module
     * @param string $controller
     * @param string $action
     * @return string
     * @throws RouteException
     */
    protected static function actionName($appType, $module, $controller, $action)
    {
        if (($appType = trim($appType)) === '') {
            throw new RouteException(self::TAG . ' actionName(), unable to find the requested appType empty.');
        }

        if (($module = trim($module)) === '') {
            throw new RouteException(self::TAG . ' actionName(), unable to find the requested module empty.');
        }

        if (($controller = trim($controller)) === '') {
            throw new RouteException(self::TAG . ' actionName(), unable to find the requested controller empty.');
        }

        if (($action = trim($action)) === '') {
            throw new RouteException(self::TAG . ' actionName(), unable to find the requested action empty.');
        }

        return self::$actionDir . '\\' . $appType . '\\' . $module . '\\' . $controller . '\\' . $action;
    }

    /**
     * 分析路由，获取接口类型、Module名、Controller名、Action名
     *
     * @param array $segments
     * @return array
     * @throws RouteException
     */
    protected static function analyzeUri($segments)
    {
        if (! is_array($segments)) {
            throw new RouteException(self::TAG . ' analyzeUri(), the requested segments not array.');
        }

        $num = count($segments);
        if ($num !== self::SEGMENTS_NUM) {
            throw new RouteException(sprintf(
                self::TAG . ' analyzeUri(), the requested segments count "%d" wrong, must be "%d".',
                $num, self::SEGMENTS_NUM
            ));
        }

        $appType    = array_shift($segments);
        $module     = array_shift($segments);
        $controller = array_shift($segments);
        $action     = array_shift($segments);

        RouterParams::setAppType($appType);
        RouterParams::setModule($module);
        RouterParams::setController($controller);
        RouterParams::setAction($action);

        return [
            RouterParams::getAppType(true),
            RouterParams::getModule(true),
            RouterParams::getController(true),
            RouterParams::getAction(true)
        ];
    }
}
