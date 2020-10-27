<?php
namespace Lyndon\Validators;


use Illuminate\Support\Facades\Validator;
use Lyndon\Constant\CodeType;
use Lyndon\Exceptions\ValidatorException;

/**
 * Class BaseValidator
 * @package Lyndon\Validators
 */
abstract class BaseValidator
{
    /**
     * @var array 验证规则
     */
    protected $rules = [];

    /**
     * @var array 错误提示信息
     */
    protected $message = [];

    /**
     * @var array 额外错误提示信息
     */
    protected $extraMessage = [];

    /**
     * @var array 待验证数据
     */
    protected $validateData = [];

    /**
     * @var array 错误验证信息
     */
    protected $validateError = [];

    /**
     * 添加待验证数据
     *
     * @param $data
     * @return $this
     */
    public function with($data)
    {
        if (is_array($data)) {
            $this->validateData = $data;
        } elseif (is_object($data)) {
            $temp = json_decode(json_encode($data), true);
            $this->validateData = empty($temp) ? [] : $temp;
        } else {
            $temp = json_decode($data);
            $this->validateData = empty($temp) ? [] : [$data];
        }

        return $this;
    }

    /**
     * 追加额外错误提示信息
     *
     * @param array $extraMessage
     * @return $this
     */
    public function pushMessage($extraMessage = [])
    {
        $this->extraMessage = $extraMessage;

        return $this;
    }

    /**
     * 表单验证，进行异常抛出
     *
     * @param $rules
     * @throws ValidatorException
     */
    public function passesOrFail($rules)
    {
        $args = func_get_args();
        if (! is_array($rules)) {
            $rules = $args;
        } elseif (count($args) > 1) {
            // 排除第一个参数自定义数组的情况
            // 形如：passesOrFail(['id' => 'required'], 'RuleName');
            $rules = $args;
        }
        unset($args);

        // 汇总各验证规则
        $validatorRule = [];
        foreach ($rules as $ruleKey) {
            // 此处使用+，说明相同规则，以最先出现的规则为准
            if (is_array($ruleKey)) {
                $validatorRule = $validatorRule + $ruleKey;
            } elseif (isset($this->rules[$ruleKey])) {
                $validatorRule = $validatorRule + $this->rules[$ruleKey];
            }
        }

        if (! empty($validatorRule)) {
            $validate = Validator::make(
                $this->validateData,
                $validatorRule,
                $this->message + $this->extraMessage
            );

            if ($validate->fails()) {
                $this->validateError = $validate->errors()->all();
                throw new ValidatorException('表单验证失败', CodeType::FORM_VALIDATOR_ERROR);
            }
        }
    }

    /**
     * 获取错误验证信息
     *
     * @return array
     */
    public function getErrorData()
    {
        return $this->validateError;
    }
}
