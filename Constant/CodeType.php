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
    const TOKEN_ERROR = 4100;
    // token签名不合法
    const TOKEN_INVALID = 4110;
    // token签名过期
    const TOKEN_EXPIRE = 4120;
    // token在某个时间之前不可用
    const TOKEN_BEFORE_NOT = 4130;

    // 表单验证错误类型
    const FORM_VALIDATOR_ERROR = 4200;

    // 模型空添加
    const MODEL_EMPTY_ADD = 4300;
    // 模型空编辑
    const MODEL_EMPTY_EDIT = 4310;

    // 分布式锁竞争失败
    const DISTRIBUTED_LOCK = 4400;
}
