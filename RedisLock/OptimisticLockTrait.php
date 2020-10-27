<?php
namespace Lyndon\RedisLock;


use Illuminate\Support\Facades\Redis;

/**
 * Trait OptimisticLockTrait
 * @package Lyndon\RedisLock
 */
trait OptimisticLockTrait
{
    /**
     * 乐观锁redis对象
     *
     * @var null
     */
    private static $optimisticLock = null;

    /**
     * 添加乐观锁
     *
     * @param string $redisKey
     * @param mixed $redisValue
     * @param int $expired
     * @return bool
     */
    protected function addOptimisticLock($redisKey, $redisValue = 1, $expired = 10)
    {
        $redisObj = $this->initOptimisticLockRedis();

        if ($redisObj->setnx($redisKey, $redisValue) === 1) {
            // 锁竞争成功，设置过期时间
            $redisObj->expire($redisKey, $expired);

            return true;
        } else {
            return false;
        }
    }

    /**
     * 删除乐观锁
     *
     * @param string $redisKey
     */
    protected function delOptimisticLock($redisKey)
    {
        $redisObj = $this->initOptimisticLockRedis();

        $redisObj->del($redisKey);
    }

    /**
     * 初始化乐观锁redis实例
     *
     * @return null
     */
    private function initOptimisticLockRedis()
    {
        if (is_null(self::$optimisticLock)) {
            self::$optimisticLock = Redis::connection('optimisticLock');
        }

        return self::$optimisticLock;
    }
}
