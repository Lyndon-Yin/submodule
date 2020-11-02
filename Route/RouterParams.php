<?php
namespace Lyndon\Route;


use Illuminate\Support\Str;

/**
 * Class RouterParams
 * @package Lyndon\Route
 */
class RouterParams
{
    const TAG = 'RouterParams';

    /**
     * @var string 接口类型，全小写
     */
    private static $appType = '';

    /**
     * @var string Module名，全小写
     */
    private static $module = '';

    /**
     * @var string Controller名，全小写
     */
    private static $controller = '';

    /**
     * @var string Action名，全小写
     */
    private static $action = '';

    /**
     * 获取接口类型
     *
     * @param bool $camel 是否转驼峰式，如果=false，返回全小写
     * @return string
     */
    public static function getAppType($camel = false)
    {
        return $camel ? self::toCamel(self::$appType) : self::$appType;
    }

    /**
     * 设置接口类型
     *
     * @param $appType
     */
    public static function setAppType($appType)
    {
        self::$appType = self::toClean($appType);
    }

    /**
     * 获取Module名
     *
     * @param bool $camel 是否转驼峰式，如果=false，返回全小写
     * @return string
     */
    public static function getModule($camel = false)
    {
        return $camel ? self::toCamel(self::$module) : self::$module;
    }

    /**
     * 设置Module名
     *
     * @param $module
     */
    public static function setModule($module)
    {
        self::$module = self::toClean($module);
    }

    /**
     * 获取Controller名
     *
     * @param bool $camel 是否转驼峰式，如果=false，返回全小写
     * @return string
     */
    public static function getController($camel = false)
    {
        return $camel ? self::toCamel(self::$controller) : self::$controller;
    }

    /**
     * 设置Controller名
     *
     * @param $controller
     */
    public static function setController($controller)
    {
        self::$controller = self::toClean($controller);
    }

    /**
     * 获取Action名
     *
     * @param bool $camel 是否转驼峰式，如果=false，返回全小写
     * @return string
     */
    public static function getAction($camel = false)
    {
        return $camel ? self::toCamel(self::$action) : self::$action;
    }

    /**
     * 设置Action名
     *
     * @param $action
     */
    public static function setAction($action)
    {
        self::$action = self::toClean($action);
    }

    /**
     * 清理字符串，如：' User_Login  ' -> 'user_login'
     *
     * @param $value
     * @return string
     */
    public static function toClean($value)
    {
        return strtolower(trim($value));
    }

    /**
     * 字符串转驼峰式，如：user_login -> UserLogin
     *
     * @param $value
     * @return string
     */
    public static function toCamel($value)
    {
        return Str::studly($value);
    }
}
