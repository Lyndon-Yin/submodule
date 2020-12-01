<?php
namespace Lyndon;

/**
 * Class HashIds
 * @package Lyndon
 */
class HashIds
{
    private static $hashIdsObj = null;

    /**
     * 加密
     *
     * @param mixed ...$numbers
     * @return string
     */
    public static function encode(...$numbers)
    {
        return self::getHashIdsObj()->encode(...$numbers);
    }

    /**
     * 解密
     *
     * @param string $hash
     * @return array
     */
    public static function decode(string $hash)
    {
        return self::getHashIdsObj()->decode($hash);
    }

    /**
     * @return \Hashids\Hashids
     */
    private static function getHashIdsObj()
    {
        if (is_null(self::$hashIdsObj)) {
            // md5('lyndon')
            self::$hashIdsObj = new \Hashids\Hashids('ccc30ace8982fe788188a1512b6daf0e', 6);
        }

        return self::$hashIdsObj;
    }
}
