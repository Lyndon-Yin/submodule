<?php
namespace Lyndon\CurlApi;


use Closure;
use Illuminate\Http\Request;
use Lyndon\PublicDB\Actions\AppServiceAction;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * Class CheckApiMiddleware
 * @package Lyndon\CurlApi
 */
class CheckApiMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        header("Content-Type:application/json;charset=utf8");

        // dev环境不进行签名验证
        if (env('APP_ENV') == 'dev') {
            return $next($request);
        }

        $sign = $request->header('Sign-Key', '');
        if (empty($sign)) {
            return response(error_return('缺少签名'));
        }

        // 获取当前应用的令牌
        try {
            $appInfo = AppServiceAction::getAppInfo(env('APP_NAME'), env('APP_ENV'));
        } catch (InvalidArgumentException $e) {
            return response(error_return($e->getMessage(), $e->getCode()));
        }
        if (empty($appInfo['app_key'])) {
            return response(error_return('令牌错误'));
        }

        // 签名验证
        $data = [
            'arrive_key'  => $appInfo['app_key'],
            'arrive_name' => env('APP_NAME'),
            'from_name'   => $request->header('Referer-Name', ''),
            'timestamp'   => $request->header('Request-Time', 0)
        ];
        if (make_sign_key($data) != $sign) {
            return response(error_return('签名错误'));
        }

        return $next($request);
    }
}
