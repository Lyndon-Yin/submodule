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

if (! function_exists('parser_search_data')) {
    /**
     * 解析$search参数
     *
     * 形如：
     * $search = "name::lyndon;age::18"
     * 结果：
     * [
     *    "name" => "lyndon"
     *    "age" => "18"
     * ]
     *
     * @param string $search
     * @return array
     */
    function parser_search_data($search)
    {
        $result = [];

        if (stripos($search, '::') !== false) {
            $fields = explode(';', $search);

            foreach ($fields as $row) {
                if (empty($row)) {
                    continue;
                }

                $field_parts = explode('::', $row);
                if (count($field_parts) !== 2) {
                    continue;
                }

                $field_parts[0] = trim($field_parts[0]);
                $field_parts[1] = trim($field_parts[1]);

                // $field_parts[1]有可能是0这样的empty值
                if (empty($field_parts[0]) || $field_parts[1] === '') {
                    continue;
                }

                $result[$field_parts[0]] = $field_parts[1];
            }
        }

        return $result;
    }
}

if (! function_exists('parser_order_by')) {
    /**
     * 解析$orderBy参数
     *
     * 形如：
     * $orderBy = "age::asc;name::desc"
     * 结果：
     * [
     *    "age" => "asc",
     *    "name" => "desc"
     * ]
     *
     * @param string $orderBy
     * @return array
     */
    function parser_order_by($orderBy)
    {
        $result = [];

        $orderBy = explode(';', $orderBy);
        foreach ($orderBy as $val) {
            if (empty($val)) {
                continue;
            }

            // 排序字段名称
            $val = explode('::', $val, 2);
            if (empty($val[0])) {
                continue;
            }
            $val[0] = trim($val[0]);

            // 排序方向，默认正序
            if (empty($val[1])) {
                $sortedBy = 'asc';
            } else {
                $sortedBy = strtolower($val[1]);
                if (! in_array($sortedBy, ['asc', 'desc'])) {
                    $sortedBy = 'asc';
                }
            }

            $result[$val[0]] = $sortedBy;
        }

        return $result;
    }
}

if (! function_exists('format_fields_searchable')) {
    /**
     * 格式化可查询条件condition
     *
     * 形如：
     * $fieldsSearchable = [
     *    'name'  => 'like',
     *    'birth' => 'between',
     *    'age'
     * ]
     * 改成：
     * $fieldsSearchable = [
     *    'name'  => 'like',
     *    'birth' => 'between',
     *    'age'   => '='
     * ]
     *
     * @param array $fieldsSearchable
     * @return array
     */
    function format_fields_searchable(array $fieldsSearchable)
    {
        $result = [];

        if (empty($fieldsSearchable)) {
            return $result;
        }

        foreach ($fieldsSearchable as $field => $condition) {
            if (is_numeric($field)) {
                $field = $condition;
                $condition = '=';
            }

            $field = trim($field);
            $result[$field] = strtolower(trim($condition));
        }

        return $result;
    }
}
