<?php
namespace Lyndon\PublicDB\Actions;


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
     */
    public static function getAppInfo($appName, $appEnv)
    {
        $results = AppServiceModel::query()
            ->where('app_name', $appName)
            ->where('app_env', $appEnv)
            ->first();

        return empty($results) ? [] : $results->toArray();
    }
}
