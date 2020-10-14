<?php
namespace Lyndon\TokenAuth;


use Firebase\JWT\JWT;
use Lyndon\Constant\CodeType;
use Lyndon\Exceptions\TokenAuthException;
use Lyndon\Logger\Log;

/**
 * Class MerchantAuth
 * @package Lyndon\TokenAuth
 */
class MerchantAuth
{
    private static $jwt_key = '';

    private static $jwt_alg = 'HS256';

    /**
     * 生成Token
     *
     * @param $data
     * @return string
     */
    public static function createMerchantToken($data)
    {
        self::initJWTKey();

        $payload = [
            // 签发时间
            'iat' => time(),
            // 自定义信息
            'data' => $data
        ];

        return JWT::encode($payload, self::$jwt_key, self::$jwt_alg);
    }

    /**
     * 验证Token
     *
     * @param $accessToken
     * @return object
     * @throws TokenAuthException
     */
    public static function verifyAccessToken($accessToken)
    {
        self::initJWTKey();

        try {
            $decoded = JWT::decode($accessToken, self::$jwt_key, [self::$jwt_alg]);
        } catch(\Firebase\JWT\SignatureInvalidException $e) {
            Log::filename('MerchantAuth')->error('MerchantAuth', form_exception_msg($e));
            throw new TokenAuthException('Token签名不合法', CodeType::TOKEN_INVALID);
        } catch(\Firebase\JWT\BeforeValidException $e) {
            Log::filename('MerchantAuth')->error('MerchantAuth', form_exception_msg($e));
            throw new TokenAuthException('Token签名暂不可用', CodeType::TOKEN_BEFORE_NOT);
        } catch(\Firebase\JWT\ExpiredException $e) {
            Log::filename('MerchantAuth')->error('MerchantAuth', form_exception_msg($e));
            throw new TokenAuthException('Token签名过期', CodeType::TOKEN_EXPIRE);
        } catch(\Exception $e) {
            Log::filename('MerchantAuth')->error('MerchantAuth', form_exception_msg($e));
            throw new TokenAuthException('Token签名错误', CodeType::TOKEN_ERROR);
        }

        return $decoded;
    }

    /**
     * 初始化jwt加密密码
     *
     * @return \Illuminate\Config\Repository|mixed|string
     */
    private static function initJWTKey()
    {
        if (empty(self::$jwt_key)) {
            self::$jwt_key = config('JWTAuth.merchantAuth', null);
            if (empty(self::$jwt_key)) {
                self::$jwt_key = 'lyndon2merchant';
            }
        }

        return self::$jwt_key;
    }
}
