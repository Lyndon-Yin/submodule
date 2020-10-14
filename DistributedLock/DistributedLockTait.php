<?php
namespace Lyndon\DistributedLock;


use Illuminate\Support\Facades\Redis;

/**
 * 分布式锁
 *
 * Trait DistributedLockTait
 * @package Lyndon\DistributedLock
 */
trait DistributedLockTait
{
    /**
     * 分布式锁redis实例
     *
     * @var null
     */
    private static $distributeLockRedis = null;

    /**
     * 分布式锁key值
     *
     * @var array
     */
    private static $distributeLockKey = [];

    /**
     * 分布式锁key前缀
     *
     * @var string
     */
    private $localPrefix = '';

    /**
     * 分布式锁竞争
     *
     * @param string $lockPrefix
     * @param string $lockLevel 锁级别，class，module
     * @param int $expired 过期时间，秒
     * @return bool
     * @throws \Exception
     */
    public function addDistributedLock($lockPrefix = 'default', $lockLevel = 'class', $expired = 10)
    {
        $this->localPrefix = $lockPrefix;

        $this->initDistributeLockKey();
        $this->initDistributeLockRedis();

        switch ($lockLevel) {
            case 'module':
                $this->addModuleDistributedLock($expired);
                break;
            case 'class':
            default:
                $this->addClassDistributedLock($expired);
                break;
        }

        return true;
    }

    /**
     * 释放分布式锁
     *
     * @param string $lockPrefix
     * @param string $lockLevel 锁级别，class，module
     */
    public function delDistributedLock($lockPrefix = 'default', $lockLevel = 'class')
    {
        $this->localPrefix = $lockPrefix;

        $this->initDistributeLockKey();
        $this->initDistributeLockRedis();

        switch ($lockLevel) {
            case 'module':
                $this->delModuleDistributedLock();
                break;
            case 'class':
            default:
                $this->delClassDistributedLock();
                break;
        }
    }

    /**
     * 添加class锁
     *
     * @param int $expired
     * @return bool
     * @throws \Exception
     */
    private function addClassDistributedLock($expired)
    {
        $redisObj = self::$distributeLockRedis;
        $redisKey = self::$distributeLockKey[$this->localPrefix];

        /* 验证是否存在module锁 */
        if ($redisObj->exists($redisKey['module'])) {
            throw new \Exception('稍后再试一下！', 4300);
        }

        /* 尝试添加class锁 */
        $redisValue = $redisKey['class'] . '@' . strval(time() + $expired);
        if ($redisObj->setnx($redisKey['class'], $redisValue) === 1) {
            // 锁竞争成功，设置过期时间
            $redisObj->expire($redisKey['class'], $expired);

            // 添加意向锁
            $this->addIntentionLock('IModule', $redisValue);

            return true;
        } else {
            throw new \Exception('稍后再试一下！', 4300);
        }
    }

    /**
     * 删除class锁
     */
    private function delClassDistributedLock()
    {
        $redisObj = self::$distributeLockRedis;
        $redisKey = self::$distributeLockKey[$this->localPrefix];

        $member = $redisObj->get($redisKey['class']);
        if (! empty($member)) {
            // 删除class锁
            $redisObj->del($redisKey['class']);
            // 删除意向锁
            $this->delIntentionLock('IModule', $member);
        }
    }

    /**
     * 添加module锁
     *
     * @param int $expired
     * @return bool
     * @throws \Exception
     */
    private function addModuleDistributedLock($expired)
    {
        $redisObj = self::$distributeLockRedis;
        $redisKey = self::$distributeLockKey[$this->localPrefix];

        /* 验证是否存在class锁，验证意向锁即可 */
        if ($this->existsIntentionLock('IModule')) {
            throw new \Exception('稍后再试一下！', 4300);
        }

        /* 尝试加module锁 */
        if ($redisObj->setnx($redisKey['module'], 1) === 1) {
            // 锁竞争成功，设置过期时间
            $redisObj->expire($redisKey['module'], $expired);

            return true;
        } else {
            throw new \Exception('稍后再试一下！', 4300);
        }
    }

    /**
     * 删除module锁
     */
    private function delModuleDistributedLock()
    {
        $redisObj = self::$distributeLockRedis;
        $redisKey = self::$distributeLockKey[$this->localPrefix];

        $redisObj->del($redisKey['module']);
    }

    /**
     * 添加意向锁
     *
     * @param string $index
     * @param string $member
     */
    private function addIntentionLock($index, $member)
    {
        $redisObj = self::$distributeLockRedis;
        $redisKey = self::$distributeLockKey[$this->localPrefix];

        $redisObj->sadd($redisKey[$index], $member);
    }

    /**
     * 删除意向锁
     *
     * @param string $index
     * @param string $member
     */
    private function delIntentionLock($index, $member)
    {
        $redisObj = self::$distributeLockRedis;
        $redisKey = self::$distributeLockKey[$this->localPrefix];

        // 删除当前成员
        $redisObj->srem($redisKey[$index], $member);
        // 循环删除所有过期成员
        $members = $redisObj->smembers($redisKey[$index]);
        if (! empty($members)) {
            $timestamp = time();
            foreach ($members as $member) {
                $expired = (int)substr($member, strpos($member, '@') + 1);
                if ($expired <= $timestamp) {
                    $redisObj->srem($redisKey[$index], $member);
                }
            }
        }
    }

    /**
     * 是否存在意向锁
     *
     * @param string $index
     * @return bool
     */
    private function existsIntentionLock($index)
    {
        $redisObj = self::$distributeLockRedis;
        $redisKey = self::$distributeLockKey[$this->localPrefix];

        // 存在，且有未过期成员。顺便移除部分过期成员
        $members = $redisObj->smembers($redisKey[$index]);
        if (! empty($members)) {
            $timestamp = time();
            foreach ($members as $member) {
                $expired = (int)substr($member, strpos($member, '@') + 1);
                if ($expired > $timestamp) {
                    return true;
                } else {
                    // 删除当前过期成员
                    $redisObj->srem($redisKey[$index], $member);
                }
            }
        }

        return false;
    }

    /**
     * 初始化分布式redis实例
     *
     * @return null
     */
    private function initDistributeLockRedis()
    {
        if (is_null(self::$distributeLockRedis)) {
            self::$distributeLockRedis = Redis::connection('distributedLock');
        }

        return self::$distributeLockRedis;
    }

    /**
     * 初始化分布式锁key值
     *
     * @return array
     */
    private function initDistributeLockKey()
    {
        if (empty(self::$distributeLockKey[$this->localPrefix])) {
            $clazz = explode('\\', get_class($this));

            $lockPrefix = 'DL:' . $this->localPrefix . ':';
            $className  = array_pop($clazz);
            $moduleName = array_pop($clazz);

            self::$distributeLockKey[$this->localPrefix] = [
                'class' => $lockPrefix . $className,
                'module' => $lockPrefix . $moduleName,
                'IModule' => $lockPrefix . 'IX:' . $moduleName
            ];
        }

        return self::$distributeLockKey[$this->localPrefix];
    }
}
