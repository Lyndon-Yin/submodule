<?php
namespace Lyndon\CurlApi;


use Curl\Curl;
use Lyndon\Logger\Log;
use Lyndon\Constant\CodeType;
use Lyndon\PublicDB\Actions\AppServiceAction;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * Class BaseCurlApi
 * @package Lyndon\CurlApi
 */
abstract class BaseCurlApi
{
    /**
     * 访问应用的APP_NAME
     *
     * @var string
     */
    protected $arriveName = '';

    /**
     * 自定义header头列表
     *
     * @var array
     */
    protected $diyHeaders = [];

    /**
     * 访问应用的信息列表
     *
     * @var array
     */
    private $arriveInfo = [];

    /**
     * 访问header头列表
     *
     * @var array
     */
    private $headers = [];

    /**
     * BaseCurlApi constructor.
     * @throws \Exception
     */
    protected function __construct()
    {
        $this->initArriveAppInfo();
    }

    /**
     * POST请求
     *
     * @param string $url
     * @param array $data
     * @return array
     */
    public function post(string $url, array $data)
    {
        return $this->curl($url, $data, 'post');
    }

    /**
     * GET请求
     *
     * @param string $url
     * @param array $data
     * @return array
     */
    public function get(string $url, array $data)
    {
        return $this->curl($url, $data, 'get');
    }

    /**
     * DELETE请求
     *
     * @param string $url
     * @param array $data
     * @return array
     */
    public function delete(string $url, array $data)
    {
        return $this->curl($url, $data, 'delete');
    }

    /**
     * 创建curl连接并返回结果
     *
     * @param string $url
     * @param array $data
     * @param string $method
     * @return array
     */
    protected function curl($url, $data, $method = 'get')
    {
        try {
            // 初始化访问应用url地址
            $url = $this->initUrl($url);
            // 初始化访问头
            $this->initHeaders();
        } catch (\Exception $e) {
            return error_return($e->getMessage());
        }

        $curl = new Curl();
        // 设置头信息
        foreach ($this->headers as $key => $val) {
            $curl->setHeader($key, $val);
        }
        foreach ($this->diyHeaders as $key => $val) {
            $curl->setHeader($key, $val);
        }

        $result = $curl->$method($url, $data);

        if ($curl->error_code) {
            Log::filename('BaseCurlApi')->error('BaseCurlApi@curl', [
                'msg'    => $curl->error_message,
                'url'    => $url,
                'data'   => $data,
                'method' => $method,
                'header' => array_merge($this->headers, $this->diyHeaders)
            ]);
            return error_return('协助异常：' . $curl->error_message, $curl->error_code);
        }

        $response = json_decode($result->response, true);
        if (is_null($response)) {
            Log::filename('BaseCurlApi')->error('BaseCurlApi@curl', [
                'url'    => $url,
                'header' => array_merge($this->headers, $this->diyHeaders),
                'data'   => $data,
                'result' => $result->response
            ]);

            return [
                'status'  => false,
                'code'    => CodeType::ERROR_RETURN,
                'message' => '远程调用失败',
                'data'    => [$result->response]
            ];
        } else {
            return [
                'status'  => $response['status'],
                'code'    => $response['code'],
                'message' => $response['message'],
                'data'    => $response['data']
            ];
        }
    }

    /**
     * 初始化url地址
     *
     * @param string $url
     * @return string
     * @throws \Exception
     */
    protected function initUrl($url)
    {
        if (empty($this->arriveInfo['app_url'])) {
            throw new \Exception('协助异常：未找到访问应用地址');
        }

        return $this->arriveInfo['app_url'] . '/' . trim($url, '\\/');
    }

    /**
     * 初始化访问头信息
     *
     * @throws \Exception
     */
    protected function initHeaders()
    {
        $currentTime = time();

        // 生成访问令牌
        if (empty($this->arriveInfo['app_key'])) {
            throw new \Exception('协助异常：请求令牌错误');
        }
        $data = [
            'arrive_key'  => $this->arriveInfo['app_key'],
            'arrive_name' => $this->arriveName,
            'timestamp'   => $currentTime,
            'from_name'   => env('APP_NAME')
        ];
        $sign = make_sign_key($data);

        $this->headers = [
            'Trace-Id'     => get_trace_id(),
            'Referer-Name' => env('APP_NAME'),
            'Referer'      => env('APP_URL'),
            'Request-Time' => $currentTime,
            'Sign-Key'     => $sign
        ];
    }

    /**
     * 获取访问应用信息
     *
     * @throws \Exception
     */
    protected function initArriveAppInfo()
    {
        if (empty($this->arriveName)) {
            throw new \Exception('协助异常：访问应用名称不能为空');
        }

        // 根据业务名称获取业务信息
        try {
            $info = AppServiceAction::getAppInfo($this->arriveName, env('APP_ENV'));
        } catch (InvalidArgumentException $e) {
            throw new \Exception('协助异常：' . $e->getMessage(), $e->getCode());
        }

        $this->arriveInfo = [
            'app_url' => trim($info['app_url'], '\\/'),
            'app_key' => $info['app_key'],
        ];
    }
}
