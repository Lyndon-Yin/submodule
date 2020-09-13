<?php
namespace Lyndon\Constant;

/**
 * Class CodeType
 * @package Lyndon\Constant
 */
final class CodeType
{
    // 普通错误返回
    const ERROR_RETURN = 400;
    // 普通正确返回
    const SUCCESS_RETURN = 200;

    // token错误
    const TOKEN_ERROR = 410;
    // token签名不合法
    const TOKEN_INVALID = 411;
    // token签名过期
    const TOKEN_EXPIRE = 412;
    // token在某个时间之前不可用
    const TOKEN_BEFORE_NOT = 413;

    // 表单验证错误类型
    const FORM_VALIDATOR_ERROR = 422;

}
