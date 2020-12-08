<?php

use Lyndon\HashIds;
use Firebase\JWT\JWT;
use Lyndon\Constant\CodeType;
use Lyndon\Exceptions\TokenAuthException;


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
            'trace'   => get_trace_id()
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
            'trace'   => get_trace_id()
        ];

        return $result;
    }
}

if (! function_exists('hash_ids_encode')) {
    /**
     * \Hashids\Hashids加密
     *
     * @param mixed ...$numbers
     * @return string
     */
    function hash_ids_encode(...$numbers)
    {
        return HashIds::encode(...$numbers);
    }
}

if (! function_exists('hash_ids_decode')) {
    /**
     * \Hashids\Hashids解密
     *
     * @param string $hash
     * @return int
     */
    function hash_ids_decode(string $hash)
    {
        return current(HashIds::decode($hash));
    }
}

if (! function_exists('hash_ids_decode_batch')) {
    /**
     * \Hashids\Hashids解密（批量）
     *
     * @param array $hashArray
     * @return array
     */
    function hash_ids_decode_batch(array $hashArray)
    {
        $results = [];

        foreach ($hashArray as $hash) {
            $temp = HashIds::decode($hash);
            if (! empty($temp)) {
                $results[] = current($temp);
            }
        }

        return $results;
    }
}

if (! function_exists('make_sign_key')) {
    /**
     * 生成跨服务请求签名
     *
     * @param array $param
     * @return string
     */
    function make_sign_key(array $param)
    {
        ksort($param);

        $sign = [];
        foreach ($param as $key => $val) {
            if (empty($val)) {
                continue;
            } elseif (is_array($val)) {
                $val = json_encode($val);
            }

            $sign[] = md5($key . '=' . $val);
        }

        return strtoupper(md5(implode('&', $sign)));
    }
}

if (! function_exists('get_trace_id')) {
    /**
     * 获取trace_id，主要用于日志追踪
     *
     * @return string
     */
    function get_trace_id()
    {
        static $traceId;

        if (empty($traceId)) {
            // 优先从header头取trace_id，这样跨服务请求会有同一个trace_id
            $traceId = request()->header('Trace-Id', null);
            if (is_null($traceId)) {
                $traceId = (string)\Illuminate\Support\Str::uuid();

                $traceId = strtoupper(str_replace('-', '', $traceId));
            }
        }

        return $traceId;
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

if (! function_exists('array_group')) {
    /**
     * 二维数组根据某个字段分组
     *
     * @param array $param
     * @param string $groupKey
     * @param null $itemKey
     * @param null $itemValue
     * @return array
     */
    function array_group(array $param, $groupKey, $itemKey = null, $itemValue = null)
    {
        $result = [];

        if (is_null($itemKey)) {
            foreach ($param as $val) {
                $result[$val[$groupKey]][] = is_null($itemValue) ? $val : $val[$itemValue];
            }
        } else {
            foreach ($param as $val) {
                $result[$val[$groupKey]][$val[$itemKey]] = is_null($itemValue) ? $val : $val[$itemValue];
            }
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

if (! function_exists('create_jwt_token')) {
    /**
     * 生成jwt token
     *
     * @param array $data
     * @param string $jwtKey
     * @param float $expire 过期时间（小时）
     * @param string $jwtAlg
     * @return string
     */
    function create_jwt_token(array $data, string $jwtKey, float $expire = -1, string $jwtAlg = 'HS256')
    {
        $payload = [
            // 签发时间
            'iat' => time(),
            // 自定义信息
            'data' => $data
        ];

        // 过期时间追加
        if ($expire > 0) {
            $payload['exp'] = time() + $expire * 3600;
        }

        return JWT::encode($payload, $jwtKey, $jwtAlg);
    }
}

if (! function_exists('verify_jwt_token')) {
    /**
     * 验证jwt token合法性
     *
     * @param string $jwtToken
     * @param string $jwtKey
     * @param array $jwtAlg
     * @return object
     * @throws TokenAuthException
     */
    function verify_jwt_token(string $jwtToken, string $jwtKey, array $jwtAlg = ['HS256'])
    {
        try {
            $decoded = JWT::decode($jwtToken, $jwtKey, $jwtAlg);
        } catch(\Firebase\JWT\SignatureInvalidException $e) {
            throw new TokenAuthException('Token签名不合法', CodeType::TOKEN_INVALID);
        } catch(\Firebase\JWT\BeforeValidException $e) {
            throw new TokenAuthException('Token签名暂不可用', CodeType::TOKEN_BEFORE_NOT);
        } catch(\Firebase\JWT\ExpiredException $e) {
            throw new TokenAuthException('Token签名过期', CodeType::TOKEN_EXPIRE);
        } catch(\Exception $e) {
            throw new TokenAuthException('Token签名错误', CodeType::TOKEN_ERROR);
        }

        return $decoded;
    }
}
