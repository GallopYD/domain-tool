<?php

namespace App\Utils;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

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
     * 获取代理
     * @return bool|string
     */
    public static function getProxy()
    {
        if (!$proxy = Redis::get('proxy')) {
            $proxy = self::getProxyList();
        }
        if (!$proxy) {
            Log::info("获取代理失败");
        }
        return $proxy;
    }

    /**
     * 获取代理列表
     * 格式：TXT
     * @return null
     */
    public static function getProxyList()
    {
        $proxy = null;
        $import_url = config('tool.proxy_host');
        if (!Redis::llen('proxy_list')) {
            if ($import_url) {
                $data = file_get_contents($import_url);
                $proxies = array_values(explode("\n", $data));
                foreach ($proxies as $proxy) {
                    Redis::rpush('proxy_list', trim($proxy));
                }
            }
        }
        $proxy = Redis::lpop('proxy_list');
        return $proxy;
    }
}