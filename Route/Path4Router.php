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

        return self::analyzeUri($segments);
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
     * 分析路由，获取接口类型、Module名、Controller名、Action名
     *
     * @param array $segments
     * @return string
     * @throws RouteException
     */
    protected static function analyzeUri($segments)
    {
        if (! is_array($segments)) {
            throw new RouteException(self::TAG . ' analyzeUri(), the requested segments not array.');
        }

        // 分离出action名称
        RouterParams::setAction(array_pop($segments));
        $action = RouterParams::getAction(true);
        if (empty($action)) {
            throw new RouteException(self::TAG . ' analyzeUri(), the action can not be empty');
        }

        $completeActionName = $action;

        // 分离出controller名称
        RouterParams::setController(array_pop($segments));
        $controller = RouterParams::getController(true);
        if (empty($controller)) {
            throw new RouteException(self::TAG . ' analyzeUri(), the controller can not be empty');
        }

        $completeActionName = $controller . '\\' . $completeActionName;

        // 分离出module名称
        RouterParams::setModule(array_pop($segments));
        $module = RouterParams::getModule(true);
        if (! empty($module)) {
            $completeActionName = $module . '\\' . $completeActionName;

            // 分离出appType名称
            RouterParams::setAppType(array_pop($segments));
            $appType = RouterParams::getAppType(true);
            if (! empty($appType)) {
                $completeActionName = $appType . '\\' . $completeActionName;
            }
        }

        return self::$actionDir . '\\' . $completeActionName;
    }
}
