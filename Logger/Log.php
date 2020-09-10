<?php
namespace Lyndon\Logger;

/**
 * Class Log
 * @package Lyndon\Logger
 */
class Log
{
    /**
     * @var array 各日志对象
     */
    private static $loggers = [];

    /**
     * 获取日志对象
     *
     * @param string $filename
     * @return mixed
     */
    public static function filename($filename)
    {
        $filename = trim($filename);

        if (! isset(self::$loggers[$filename])) {
            self::$loggers[$filename] = new LogHandle($filename);
        }

        return self::$loggers[$filename];
    }
}
