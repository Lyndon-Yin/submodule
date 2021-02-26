<?php
namespace Lyndon\Repository\Eloquent;


use Illuminate\Support\Facades\Cache;

/**
 * Trait RepositoryCacheTrait
 * @package Lyndon\Repository\Eloquent
 */
trait RepositoryCacheTrait
{
    /**
     * 获取缓存数据行
     *
     * @param string $cacheKey
     * @return array|null
     */
    private function getRepoRowCache($cacheKey)
    {
        return Cache::get($cacheKey, null);
    }

    /**
     * 存储缓存数据行
     *
     * @param string $cacheKey
     * @param $data
     */
    private function putRepoRowCache($cacheKey, $data)
    {
        Cache::put($cacheKey, $data, $this->cacheExpire);
    }

    /**
     * 删除缓存数据行
     *
     * @param string $cacheKey
     */
    private function forgetRepoRowCache($cacheKey)
    {
        Cache::forget($cacheKey);
    }

    /**
     * 过滤缓存获取的数据行
     *
     * @param array $data
     * @param array $extraWhere
     * @return array
     */
    private function filterRepoRowCache(array $data, array $extraWhere = [])
    {
        if (empty($extraWhere) || empty($data)) {
            return $data;
        }

        // 存在额外筛选条件，集合筛选
        $data = collect([$data]);
        foreach ($extraWhere as $key => $val) {
            if (is_array($val)) {
                $data = $data->where(array_shift($val), array_shift($val), array_shift($val));
            } else {
                $data = $data->where($key, $val);
            }
        }

        return $data->first();
    }

    /**
     * 过滤缓存获取的数据列表
     *
     * @param array $data
     * @param array $extraWhere
     * @return array|\Illuminate\Support\Collection
     */
    private function filterRepoListCache(array $data, array $extraWhere = [])
    {
        if (empty($extraWhere) || empty($data)) {
            return $data;
        }

        // 存在额外筛选条件，集合筛选
        $data = collect($data);
        foreach ($extraWhere as $key => $val) {
            if (is_array($val)) {
                $data = $data->where(array_shift($val), array_shift($val), array_shift($val));
            } else {
                $data = $data->where($key, $val);
            }
        }

        return $data->toArray();
    }

    /**
     * 获取缓存key值
     *
     * @param mixed $primaryKey
     * @return string
     */
    private function getRepoCacheKey($primaryKey)
    {
        return ':cache-repo:' . $this->model->getTable() . ':' . $primaryKey;
    }

    /**
     * 获取trashed缓存key值
     *
     * @param mixed $primaryKey
     * @return string
     */
    private function getTrashedRepoCacheKey($primaryKey)
    {
        return $this->getRepoCacheKey($primaryKey) . ':trashed';
    }
}
