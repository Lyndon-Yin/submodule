<?php
namespace Lyndon;


use Illuminate\Support\Facades\Redis;

/**
 * Class Snowflake
 * @package Lyndon
 */
class Snowflake
{
    /**
     * 起始时间戳，作为时间基准
     * 2020-10-10 12:08:39
     */
    const START_TIMESTAMP = 1602302919000;

    /**
     * 机器标识位数
     */
    const WORKER_ID_BITS = 5;

    /**
     * 数据中心标识位数
     */
    const DATA_CENTER_ID_BITS = 5;

    /**
     * 毫秒内自增序列位数
     */
    CONST SEQUENCE_BITS = 12;

    /**
     * 当前机器ID
     *
     * @var int
     */
    private $workerId = 0;

    /**
     * 当前数据中心ID
     *
     * @var int
     */
    private $dataCenterId = 0;

    /**
     * redis对象
     *
     * @var \Illuminate\Redis\Connections\Connection|null
     */
    private $redis = null;

    /**
     * Snowflake constructor.
     * @param int $workerId
     * @param int $dataCenterId
     * @throws \Exception
     */
    public function __construct(int $workerId = 0, int $dataCenterId = 0)
    {
        // 最大机器ID值
        $maxWorkerId = -1 ^ (-1 << self::WORKER_ID_BITS);
        if ($workerId < 0 || $workerId > $maxWorkerId) {
            throw new \Exception('机器ID必须大于等于0，小于等于' . $maxWorkerId);
        }

        $this->workerId = decbin($workerId);

        // 最大数据中心ID值
        $maxDataCenterId = -1 ^ (-1 << self::DATA_CENTER_ID_BITS);
        if ($dataCenterId < 0 || $dataCenterId > $maxDataCenterId) {
            throw new \Exception('数据中心ID必须大于等于0，小于等于' . $maxDataCenterId);
        }

        $this->dataCenterId = decbin($dataCenterId);

        // 初始化雪花算法redis对象，实现最后12位的序列号自增
        $this->redis = Redis::connection('snowflake');
    }

    /**
     * 获取下一个ID
     *
     * @return string
     */
    public function nextId()
    {
        // 当前时间戳（毫秒）
        $currentTime = $this->getMicrotime();

        // 获取序列号，如果一个毫秒内，序列号超过最大长度，睡眠1毫秒再次尝试
        while (($sequence = $this->getSequence($currentTime)) > (-1 ^ (-1 << self::SEQUENCE_BITS))) {
            usleep(1);
            $currentTime = $this->getMicroTime();
        }

        /**
         * 1 bit  - 符号位，正负标识位
         * 41 bit - 毫秒级时间戳
         * 5 bit  - 数据中心ID，dataCenterId
         * 5 bit  - 机器ID，workerId
         * 12 bit - 序列号，用来记录同毫秒内产生的不同ID
         */
        $workMoveLength       = self::SEQUENCE_BITS;
        $dataCenterMoveLength = $workMoveLength + self::WORKER_ID_BITS;
        $timestampMoveLength  = $dataCenterMoveLength + self::DATA_CENTER_ID_BITS;
        return (string) (
            (($currentTime - self::START_TIMESTAMP) << $timestampMoveLength)
            | ($this->dataCenterId << $dataCenterMoveLength)
            | ($this->workerId << $workMoveLength)
            | ($sequence)
        );
    }

    /**
     * 反向解析ID
     *
     * @param string $id
     * @param bool $transform
     * @return array
     */
    public function parseId($id, $transform = false)
    {
        $id = decbin($id);

        $data = [
            'timestamp'   => substr($id, 0, -22),
            'sequence'    => substr($id, -12),
            'worker_id'   => substr($id, -17, 5),
            'data_center' => substr($id, -22, 5),
        ];

        return $transform ? array_map(function ($value) {
            return bindec($value);
        }, $data) : $data;
    }

    /**
     * 获取序列号
     *
     * @param int $currentTime
     * @return int
     */
    private function getSequence(int $currentTime)
    {
        $key = 'snowflake' . $currentTime;

        // EXISTS key
        // PSETEX key EXPIRY_IN_MILLISECONDS value
        $lua = "return redis.call('exists',KEYS[1])<1 and redis.call('psetex',KEYS[1],1000,ARGV[1])";

        // EVAL script numkeys key [key ...] arg [arg ...]
        if ($this->redis->eval($lua, 1, $key, 0)) {
            return 0;
        } else {
            return $this->redis->incrby($key, 1);
        }
    }

    /**
     * 获取毫秒级时间戳
     *
     * @return float
     */
    private function getMicroTime()
    {
        return floor(microtime(true) * 1000);
    }
}
