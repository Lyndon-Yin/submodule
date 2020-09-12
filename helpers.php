<?php

if (! function_exists('success_return')) {
    /**
     * 返回正确结果
     *
     * @param string $message
     * @param int $code
     * @param array $data
     * @return array
     */
    function success_return($message = '', $code = 200, $data = [])
    {
        $result = [
            'status'  => true,
            'code'    => $code,
            'message' => $message,
            'data'    => $data,
        ];

        return $result;
    }
}

if (! function_exists('error_return')) {
    /**
     * 返回错误结果
     *
     * @param string $message
     * @param int $code
     * @param array $data
     * @return array
     */
    function error_return($message = '', $code = 400, $data = [])
    {
        $result = [
            'status'  => false,
            'code'    => $code,
            'message' => $message,
            'data'    => $data,
        ];

        return $result;
    }
}

if (! function_exists('form_exception_msg')) {
    /**
     * 格式化异常通知
     * @param Exception $e
     * @return string
     */
    function form_exception_msg(\Exception $e)
    {
        return sprintf(
            "%s in %s file at %s line\r%s",
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        );
    }
}

if (! function_exists('form_int_array')) {
    /**
     * 格式化数字类型数组，类型转换+去重
     * whereIn查询时，如果是字符串，不走索引
     * @param array $array 数字数组
     * @return array
     */
    function form_int_array($array)
    {
        $result = [];

        // 所有分类ID转换为int类型，并去重
        foreach ($array as $val) {
            $val = intval($val);
            $result[$val] = $val;
        }

        return $result;
    }
}
