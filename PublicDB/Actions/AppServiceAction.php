<?php
namespace Lyndon\PublicDB\Actions;


use Illuminate\Support\Facades\Cache;
use Lyndon\PublicDB\Models\AppServiceModel;

/**
 * Class AppServiceAction
 * @package Lyndon\PublicDB\Actions
 */
class AppServiceAction
{
    /**
     * 获取应用服务信息
     *
     * @param string $appName
     * @param string $appEnv
     * @return array
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public static function getAppInfo($appName, $appEnv)
    {
        $cacheKey = "ly:pb:" . $appName . ':' . $appEnv;

        // 从file缓存中获取
        $results = Cache::store('file')->get($cacheKey, null);
        if (! is_null($results)) {
            return $results;
        }

        // 从redis缓存中获取
        $results = Cache::store('redis')->get($cacheKey, null);
        if (! is_null($results)) {
            // 数据加入file缓存中，过期时间为10分钟
            Cache::store('file')->put($cacheKey, $results, 600);

            return $results;
        }

        // 从数据库中获取
        $results = AppServiceModel::query()
            ->where('app_name', $appName)
            ->where('app_env', $appEnv)
            ->first();
        $results = empty($results) ? [] : $results->toArray();

        // 数据加入file缓存中，过期时间为10分钟
        Cache::store('file')->put($cacheKey, $results, 600);
        // 数据加入redis缓存，过期时间为100天
        Cache::store('redis')->put($cacheKey, $results, 8640000);

        return $results;
    }
}
