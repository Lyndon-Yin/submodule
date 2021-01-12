<?php
namespace Lyndon\Traits;

/**
 * Trait Singleton
 * @package Lyndon\Traits
 */
trait Singleton
{
    /**
     * 当前对象实例
     *
     * @var null
     */
    private static $instance = null;

    /**
     * 获取当前对象
     *
     * @param mixed ...$args
     * @return null
     * @throws \Exception
     */
    public static function getInstance(...$args)
    {
        if (is_null(self::$instance)) {
            self::$instance = new static(...$args);
        }

        return self::$instance;
    }
}
