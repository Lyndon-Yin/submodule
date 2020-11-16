<?php
namespace Lyndon\Logger;

/**
 * Class LogHandle
 * @package Lyndon\Logger
 */
class LogHandle
{
    /**
     * 信息记录
     */
    const INFO = 'info';

    /**
     * 通知错误
     */
    const NOTICE = 'notice';

    /**
     * 警告错误
     */
    const WARNING = 'warning';

    /**
     * 致命错误
     */
    const ERROR = 'error';

    /**
     * 各错误等级code码
     * @var array
     */
    private static $levels = [
        self::INFO    => 200,
        self::NOTICE  => 250,
        self::WARNING => 300,
        self::ERROR   => 400
    ];

    /**
     * @var array 日志包含的基本信息
     */
    private static $baseRecord = [];

    /**
     * @var string 日志文件名称
     */
    private $filename = '';

    /**
     * LogHandle constructor.
     * @param $filename
     */
    public function __construct($filename)
    {
        $this->filename = $filename;

        // 初始化日志基本信息
        if (empty(static::$baseRecord)) {
            static::$baseRecord = [
                'hostname' => gethostname(),
                'pid'      => getmypid(),
                'app_name' => env('APP_NAME'),
                'trace_id' => get_trace_id(),
            ];
        }
    }

    /**
     * INFO类型日志记录
     *
     * @param string $message
     * @param mixed $context
     * @return bool
     */
    public function info($message, $context = [])
    {
        return $this->addRecord(self::INFO, $message, $context);
    }

    /**
     * NOTICE类型日志记录
     *
     * @param string $message
     * @param mixed $context
     * @return bool
     */
    public function notice($message, $context = [])
    {
        return $this->addRecord(self::NOTICE, $message, $context);
    }

    /**
     * WARNING类型日志记录
     *
     * @param string $message
     * @param mixed $context
     * @return bool
     */
    public function warning($message, $context = [])
    {
        return $this->addRecord(self::WARNING, $message, $context);
    }

    /**
     * ERROR类型日志记录
     *
     * @param string $message
     * @param mixed $context
     * @return bool
     */
    public function error($message, $context = [])
    {
        return $this->addRecord(self::ERROR, $message, $context);
    }

    /**
     * 日志添加
     *
     * @param string $levelName
     * @param string $message
     * @param mixed $context
     * @return bool
     */
    public function addRecord($levelName, $message, $context)
    {
        if (is_string($context)) {
            $context = [$context];
        }
        if (! isset(self::$levels[$levelName])) {
            $levelName = 'info';
        }

        $record['datetime']  = date('Y-m-d H:i:s');
        $record['levelName'] = $levelName;
        $record['level']     = self::$levels[$levelName];
        $record['context']   = $context;
        $record['message']   = (string) $message;
        $record = $record + static::$baseRecord;

        LogWriter::logFile($this->filename . '-' . $levelName, $record);
        return true;
    }
}
