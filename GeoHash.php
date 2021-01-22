<?php
namespace Lyndon;
use Lyndon\Traits\Singleton;

/**
 * Class GeoHash
 * @package Lyndon
 */
class GeoHash
{
    use Singleton;

    // 32进制编码允许字符串
    private $base32Mapping = "0123456789bcdefghjkmnpqrstuvwxyz";

    // 最小经度
    private $minLng = -180;
    // 最大经度
    private $maxLng = 180;

    // 最小纬度
    private $minLat = -90;
    // 最大纬度
    private $maxLat = 90;

    /**
     * GeoHash constructor.
     */
    private function __construct()
    {
    }

    /**
     * 8位geohash编码
     *
     * @param mixed $lng
     * @param mixed $lat
     * @return string
     */
    public function encode($lng, $lat)
    {
        // 经纬度范围验证
        $lng = floatval($lng);
        if ($lng > $this->maxLng || $lng < $this->minLng) {
            return '';
        }
        $lat = floatval($lat);
        if ($lat > $this->maxLat || $lat < $this->minLat) {
            return '';
        }

        // 经纬度解析成二进制数组
        $bitsArray = $this->coordinateToBitsArray($lng, $lat);

        // 二进制数字转换为32进制数
        return $this->bitsArrayToGeoHash($bitsArray);
    }

    /**
     * geoHash字符串解码
     *
     * @param string $geoHash
     * @return array
     */
    public function decode($geoHash)
    {
        $result = [];

        // 非空验证
        $geoHash = trim($geoHash);
        if (empty($geoHash)) {
            return $result;
        }

        // geoHash 32位转二进制
        try {
            $bitsString = $this->geoHashToBitsString($geoHash);
        } catch (\Exception $e) {
            return $result;
        }

        // 反算出经纬度范围
        return $this->bitsStringToCoordinate($bitsString);
    }

    /**
     * 二进制字符串转换为坐标
     *
     * @param string $bitsString
     * @return array
     */
    public function bitsStringToCoordinate($bitsString)
    {
        // 初始化经纬度范围
        $minLng = $this->minLng;
        $maxLng = $this->maxLng;

        $minLat = $this->minLat;
        $maxLat = $this->maxLat;

        // 反算出经纬度范围
        $bitsLength = strlen($bitsString);
        for ($i = 0; $i < $bitsLength; $i++) {
            if ($i % 2 == 0) {
                // 偶数位，经度
                if ($bitsString[$i] == 1) {
                    $minLng = ($minLng + $maxLng) / 2;
                } else {
                    $maxLng = ($minLng + $maxLng) / 2;
                }
            } else {
                // 奇数位，纬度
                if ($bitsString[$i] == 1) {
                    $minLat = ($minLat + $maxLat) / 2;
                } else {
                    $maxLat = ($minLat + $maxLat) / 2;
                }
            }
        }

        return compact('minLng', 'maxLng', 'minLat', 'maxLat');
    }

    /**
     * geoHash转换为二进制字符串形式
     *
     * @param string $geoHash
     * @return string
     * @throws \Exception
     */
    public function geoHashToBitsString($geoHash)
    {
        $bitsString = '';

        $geoHashLength = strlen($geoHash);
        for ($i = 0; $i < $geoHashLength; $i++) {
            $position = strpos($this->base32Mapping, $geoHash[$i]);
            if ($position === false) {
                // 未知编码字符串。直接返回错误结果
                throw new \Exception('非法字符串');
            }

            // 十进制数转换为二进制
            $bitsString .= sprintf('%05b', $position);
        }

        return $bitsString;
    }

    /**
     * 二进制数组转换为geoHash
     *
     * @param array $bitsArray
     * @return string
     */
    public function bitsArrayToGeoHash($bitsArray)
    {
        $geoHash = [];

        $bitsArray = array_chunk($bitsArray, 5);
        foreach ($bitsArray as $chunk) {
            $n = bindec(implode('', $chunk));
            $geoHash[] = $this->base32Mapping[$n];
        }

        return implode('', $geoHash);
    }

    /**
     * 坐标点转换为二进制数组形式
     *
     * @param mixed $lng
     * @param mixed $lat
     * @return array
     */
    public function coordinateToBitsArray($lng, $lat)
    {
        $bitsArray = [];

        // 初始化经纬度范围
        $leftLng  = $this->minLng;
        $rightLng = $this->maxLng;

        $belowLat = $this->minLat;
        $aboveLat = $this->maxLat;

        for ($i = 0; $i < 20; $i++) {
            // 经度$lng解析
            $medianLng = ($leftLng + $rightLng) / 2;
            if ($lng > $medianLng) {
                $leftLng = $medianLng;
                $bitsArray[] = 1;
            } else {
                $rightLng = $medianLng;
                $bitsArray[] = 0;
            }

            // 纬度$lat解析
            $medianLat = ($belowLat + $aboveLat) / 2;
            if ($lat > $medianLat) {
                $belowLat = $medianLat;
                $bitsArray[] = 1;
            } else {
                $aboveLat = $medianLat;
                $bitsArray[] = 0;
            }
        }

        return $bitsArray;
    }
}
