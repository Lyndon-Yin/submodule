<?php
namespace Lyndon\PublicDB\Actions;


use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;
use Lyndon\PublicDB\Models\AppServiceModel;

/**
 * Class AppServiceAction
 * @package Lyndon\PublicDB\Actions
 */
class AppServiceAction
{
    /**
     * @var null 公共redis对象
     */
    private static $publicRedis = null;

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
        $cacheKey = "ly:pb:app:" . $appName . ':' . $appEnv;

        // 从file缓存中获取
        $results = Cache::store('file')->get($cacheKey, null);
        if (! is_null($results)) {
            return $results;
        }

        // 从redis缓存中获取
        $results = self::getRedisCache($cacheKey);
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
        // 数据加入redis缓存
        self::setRedisCache($cacheKey, $results);

        return $results;
    }

    /**
     * 应用服务列表（对外接口）
     *
     * @param array $param
     * @return mixed
     */
    public function appList(array $param = [])
    {
        return AppServiceModel::query()
            ->select('id', 'app_name', 'app_env', 'app_url')
            ->when(! empty($param['app_keyword']), function ($query) use ($param) {
                return $query->where('app_name', 'like', '%'.$param['app_keyword'].'%');
            })
            ->when(! empty($param['app_env']), function ($query) use ($param) {
                return $query->where('app_env', $param['app_env']);
            })
            ->orderBy('id', 'desc')
            ->paginate(40);
    }

    /**
     * 应用服务详情（对外接口）
     *
     * @param int $appId
     * @return array
     */
    public function appInfo($appId)
    {
        $appId = intval($appId);

        $results = AppServiceModel::query()
            ->select('id', 'app_name', 'app_env', 'app_url')
            ->where('id', $appId)
            ->first();

        return empty($results) ? [] : $results->toArray();
    }

    /**
     * 应用服务添加（对外接口）
     *
     * @param array $param
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \Exception
     */
    public function addApp($param)
    {
        // 表单验证
        if (empty($param['app_name'])) {
            throw new \Exception('应用服务名称不能为空');
        }
        if (empty($param['app_env'])) {
            throw new \Exception('应用服务环境不能为空');
        }
        $param['app_name'] = trim($param['app_name']);
        $param['app_env']  = trim($param['app_env']);

        // 验证应用服务的唯一性
        $info = AppServiceModel::query()
            ->where('app_name', $param['app_name'])
            ->where('app_env', $param['app_env'])
            ->first();
        if (! empty($info)) {
            throw new \Exception('当前环境下应用服务名称已存在');
        }

        // 数据库添加
        $data = [
            'app_name' => $param['app_name'],
            'app_env'  => $param['app_env'],
            'app_url'  => empty($param['app_url']) ? '' : trim($param['app_url']),
            'app_key'  => empty($param['app_key']) ? '' : md5(trim($param['app_key']))
        ];
        AppServiceModel::query()->insert($data);

        // 删除缓存
        $this->delAppInfoCache($param['app_name'], $param['app_env']);

        return true;
    }

    /**
     * 应用服务编辑（对外接口）
     *
     * @param int $appId
     * @param array $param
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \Exception
     */
    public function editApp($appId, $param)
    {
        $appId = intval($appId);

        // 验证应用服务存在性
        $info = AppServiceModel::query()
            ->select('app_name', 'app_env')
            ->where('id', $appId)
            ->first();
        if (empty($info)) {
            throw new \Exception('未识别应用服务');
        }

        // 验证app_name和app_env唯一性
        if (! empty($param['app_name']) || ! empty($param['app_env'])) {
            $temp = AppServiceModel::query()
                ->select('id')
                ->where('app_name', empty($param['app_name']) ? $info->app_name : trim($param['app_name']))
                ->where('app_env', empty($param['app_env']) ? $info->app_env : trim($param['app_env']))
                ->where('id', '<>', $appId)
                ->first();
            if (! empty($temp)) {
                throw new \Exception('应用服务重复');
            }
        }

        // 通行密码md5加密
        if (! empty($param['app_key'])) {
            $param['app_key'] = md5(trim($param['app_key']));
        }

        // 筛选出可编辑字段
        $column = ['app_name', 'app_env', 'app_url', 'app_key'];
        $data = array_filter($param, function ($key) use ($column) {
            return in_array($key, $column);
        }, ARRAY_FILTER_USE_KEY);
        if (empty($data)) {
            return false;
        }

        // 数据库更新
        AppServiceModel::where('id', $appId)->update($param);
        // 删除缓存
        $this->delAppInfoCache($info->app_name, $info->app_env);

        return true;
    }

    /**
     * 应用服务删除（对外接口）
     *
     * @param int $appId
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function delApp($appId)
    {
        $appId = intval($appId);

        // 验证应用服务存在性
        $info = AppServiceModel::query()
            ->select('app_name', 'app_env')
            ->where('id', $appId)
            ->first();
        if (empty($info)) {
            return true;
        }

        // 数据库删除
        AppServiceModel::where('id', $appId)->delete();
        // 缓存删除
        $this->delAppInfoCache($info->app_name, $info->app_env);

        return true;
    }

    /**
     * 删除应用服务缓存
     *
     * @param string $appName
     * @param string $appEnv
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    private function delAppInfoCache($appName, $appEnv)
    {
        $cacheKey = "ly:pb:app:" . $appName . ':' . $appEnv;

        Cache::store('file')->delete($cacheKey);
        self::getPublicRedis()->del($cacheKey);
    }

    /**
     * 设置redis缓存数据
     *
     * @param string $cacheKey
     * @param array $result
     */
    private static function setRedisCache($cacheKey, array $result)
    {
        self::getPublicRedis()->set($cacheKey, json_encode($result));
    }

    /**
     * 获取公共redis缓存数据
     *
     * @param string $cacheKey
     * @return mixed|null
     */
    private static function getRedisCache($cacheKey)
    {
        $result = self::getPublicRedis()->get($cacheKey);

        return is_null($result) ? null : json_decode($result, true);
    }

    /**
     * 获取公共redis对象
     *
     * @return \Illuminate\Redis\Connections\Connection
     */
    private static function getPublicRedis()
    {
        if (is_null(self::$publicRedis)) {
            self::$publicRedis = Redis::connection('publicRedis');
        }

        return self::$publicRedis;
    }
}
