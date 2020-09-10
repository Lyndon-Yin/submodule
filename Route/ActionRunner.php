<?php
namespace Lyndon\Route;

use Illuminate\Http\Request;
use Illuminate\Container\Container;
use Lyndon\Exceptions\RouteException;

/**
 * Class ActionRunner
 * @package Lyndon\Route
 */
class ActionRunner
{
    const TAG = 'ActionRunner';

    /**
     * 运行Action类
     *
     * @param string $clazz
     * @param Request $request
     * @return mixed
     * @throws RouteException
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public static function run($clazz, Request $request)
    {
        if (! class_exists($clazz)) {
            throw new RouteException(sprintf(
                self::TAG . ' run(), the action "%s" not exists.',
                $clazz
            ));
        }

        $instance = Container::getInstance()->make($clazz);
        if (! $instance instanceof AbstractAction) {
            throw new RouteException(sprintf(
                self::TAG . ' run(), the action "%s" is not instanceof AbstractAction.',
                $clazz
            ));
        }

        $method = $request->method();
        $supportMethods = static::supportMethods($instance);
        if (! in_array(strtoupper($method), $supportMethods)) {
            throw new RouteException(sprintf(
                self::TAG . ' run(), Request\'s method "%s" be not supported, action "%s" is supported methods "%s"',
                $method, $instance->getName(), implode(',', $supportMethods)
            ));
        }

        $instance->preReturn = $instance->preRun($request);
        $instance->onReturn  = $instance->onRun($request);
        return $instance->postRun($request);
    }

    /**
     * Action类支持的请求方式列表
     *
     * @param AbstractAction $instance
     * @return array
     * @throws RouteException
     */
    protected static function supportMethods(AbstractAction $instance)
    {
        $allowMethods = $instance->allowMethods();
        if (is_array($allowMethods)) {
            foreach ($allowMethods as &$method) {
                // $method留待异常错误返回
                $upperMethod = strtoupper($method);

                // 验证允许的请求方式是否合法
                if (! in_array($upperMethod, $instance->getMethods())) {
                    throw new RouteException(sprintf(
                        'Method "%s" in allowMethods be not supported, action "%s" is supported methods "%s"',
                        $method, $instance->getName(), implode(',', $instance->getMethods())
                    ));
                }

                $method = $upperMethod;
            }
            unset($method);
        } else {
            throw new RouteException(sprintf(
                'Method allowMethods() in Action "%s" must return array',
                $instance->getName()
            ));
        }

        $method = $instance->allowMethod();
        $upperMethod = strtoupper($method);
        if (! in_array($upperMethod, $instance->getMethods())) {
            throw new RouteException(sprintf(
                'Method "%s" return from allowMethod be not supported, action "%s" is supported methods "%s"',
                $method, $instance->getName(), implode(',', $instance->getMethods())
            ));
        }

        if (! in_array($upperMethod, $allowMethods)) {
            $allowMethods[] = $upperMethod;
        }

        if (empty($allowMethods)) {
            throw new RouteException(sprintf(
                self::TAG . ' supportMethods(), Action "%s" has no allowed method',
                $instance->getName())
            );
        }

        return $allowMethods;
    }
}
