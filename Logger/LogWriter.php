<?php
namespace Lyndon\Logger;

/**
 * Class LogWriter
 * @package Lyndon\Logger
 */
class LogWriter
{
    /**
     * @var array 各类型文件名集合
     */
    private static $filenameList = [];

    /**
     * 本地文件记录
     *
     * @param string $logName
     * @param array $record
     * @return bool|int
     */
    public static function logFile($logName, $record)
    {
        // 生成本地文件名
        if (! isset(static::$filenameList[$logName])) {
            static::$filenameList[$logName] = static::getTimedFilename($logName);
        }

        // 目录文件不存在，则创建该目录
        $dirName = dirname(static::$filenameList[$logName]);
        if (! file_exists($dirName)) {
            mkdir($dirName, 0777);
            chmod($dirName, 0777);
        }
        unset($dirName);

        // 创建文件
        if (! file_exists(static::$filenameList[$logName])) {
            $file = fopen(static::$filenameList[$logName], 'a+');
            fclose($file);
        }

        // dev环境下，更改文件权限为0777权限
        if (env('APP_ENV') == 'dev') {
            chmod(static::$filenameList[$logName], 0777);
        }

        return file_put_contents(
            static::$filenameList[$logName],
            json_encode($record, JSON_UNESCAPED_UNICODE) . PHP_EOL,
            FILE_APPEND
        );
    }

    /**
     * 获取日志文件名称
     *
     * @param $logName
     * @return string
     */
    private static function getTimedFilename($logName)
    {
        // 获取laravel目录storage地址
        $storagePath = app('path.storage');

        return $storagePath . "/logs/" . date('Y-m-d') . '/' . $logName . ".log";
    }
}
