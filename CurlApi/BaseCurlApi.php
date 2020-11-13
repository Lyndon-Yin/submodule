<?php
namespace Lyndon\CurlApi;


use Curl\Curl;
use Lyndon\Logger\Log;
use Lyndon\PublicDB\Actions\AppServiceAction;

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
     * 访问应用的URL地址
     *
     * @var string
     */
    protected $arriveUrl = '';

    /**
     * 访问header头列表
     *
     * @var array
     */
    protected $headers = [];

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
        // 初始化访问应用url地址
        try {
            $url = $this->initUrl($url);
        } catch (\Exception $e) {
            return error_return($e->getMessage());
        }

        // 初始化访问头
        $this->initHeaders();

        $curl = new Curl();
        // 设置头信息
        foreach ($this->headers as $key => $val) {
            $curl->setHeader($key, $val);
        }

        $result = $curl->$method($url, $data);

        if ($curl->error_code) {
            Log::filename('BaseCurlApi')->error('BaseCurlApi', ['msg' => $curl->error_message, 'url' => $url, 'data' => $data, 'method' => $method]);
            return error_return('协助异常：' . $curl->error_message, $curl->error_code);
        }

        $response = json_decode($result->response, true);
        return [
            'status'  => $response['status'],
            'code'    => $response['code'],
            'message' => $response['message'],
            'data'    => $response['data']
        ];
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
        return $this->getArriveUrl() . trim($url, '\\/');
    }

    /**
     * 初始化访问头信息
     */
    protected function initHeaders()
    {
        $this->headers = [
            'Referer-Name' => env('APP_NAME'),
            'Referer'      => env('APP_URL')
        ];
    }

    /**
     * 获取访问应用的URL地址
     *
     * @return string
     * @throws \Exception
     */
    protected function getArriveUrl()
    {
        if (empty($this->arriveUrl)) {
            if (empty($this->arriveName)) {
                throw new \Exception('协助异常：访问应用名称不能为空');
            }

            // 根据业务名称获取业务地址
            $info = AppServiceAction::getAppInfo($this->arriveName, env('APP_ENV'));
            if (empty($info['app_url'])) {
                throw new \Exception('协助异常：未找到访问应用地址');
            }

            $this->arriveUrl = trim($info['app_url'], '\\/') . '/';
        }

        return $this->arriveUrl;
    }
}
