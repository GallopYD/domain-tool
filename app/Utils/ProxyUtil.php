<?php

namespace App\Utils;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

/**
 * 代理
 * Class ProxyUtil
 * @package App\Utils
 */
class ProxyUtil
{
    static $proxy;
    static $try = 0;

    /**
     * 获取有效代理
     * @return bool|string
     */
    public static function getValidProxy()
    {
        if ($proxy = self::getProxyFromPool()) {
            return $proxy;
        }
        return false;
    }

    /**
     * 获取单条代理
     * @return bool|string
     */
    public static function getProxyFromPool()
    {
        //是否有获取代理URL
        if (!$proxy_pool_host = config('tool.proxy_pool_host')) {
            return false;
        } else {
            try {
                //多次获取代理，首次获取稳定代理，后面获取优质代理
                if (!self::$try) {
                    $url = $proxy_pool_host . 'api/proxies/stable';
                } else {
                    $url = $proxy_pool_host . 'api/proxies/premium';
                }
                $client = new Client();
                $response = $client->request('GET', $url, [
                    'connect_timeout' => 3,
                    'timeout' => 3,
                ]);
                $data = json_decode($response->getBody()->getContents(), true);
                $proxy = $data['data']['protocol'] . '://' . $data['data']['ip'] . ':' . $data['data']['port'];
                self::$try += 1;
                return $proxy;
            } catch (\Exception $exception) {
                Log::info("获取代理失败：" . $exception->getMessage());
            }
        }
        return false;
    }

    /**
     * 检测代理IP是否可用
     * @param $proxy
     * @return bool
     */
    public static function checkProxy($proxy)
    {
        $client = new Client();
        try {
            $client->request('GET', 'http://www.baidu.com', [
                'proxy' => $proxy,
                'connect_timeout' => 2,
                'timeout' => 2,
            ]);
            Log::info("代理[{$proxy}]可用");
            return true;
        } catch (\Exception $exception) {
            Log::info("代理[{$proxy}]不可用：" . $exception->getMessage());
        }
        return false;
    }
}