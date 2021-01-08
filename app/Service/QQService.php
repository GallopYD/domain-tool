<?php

namespace App\Service;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * Class QQService
 * @package App\Service
 */
class QQService extends BaseService
{

    /**
     * 电脑管家检测
     *
     * @param $domain
     * @return int
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function check($domain)
    {
        if (!$intercept = Redis::get("intercept:qq:{$domain}")) {
            try {
                $client = new Client();
                $options = [
                    'headers' => ['Referer' => 'https://guanjia.qq.com'],
                    'connect_timeout' => 5,
                    'timeout' => 5,
                ];
                $url = 'https://cgi.urlsec.qq.com/index.php?m=check&a=check&url=http://' . $domain;
                $response = $client->request('GET', $url, $options);
                $contents = $response->getBody()->getContents();
                $result = json_decode(substr($contents, 1, strlen($contents) - 2), true);

                // whitetype 1：安全性未知 2：危险网站 3：安全网站
                $intercept = $result['data']['results']['whitetype'] == 2 ? 2 : 1;
                Log::info("QQ拦截查询成功[电脑管家][域名：{$domain}]：{$intercept}");

            } catch (\Exception $exception) {
                $intercept = 0;
                Log::info("QQ拦截查询失败[电脑管家][域名：{$domain}]：{$exception->getMessage()}");
            }
        }

        // 检测结果缓存
        if ($intercept && $this->cache_enable) {
            Redis::setex("intercept:qq:{$domain}", 24 * 60 * 60, $intercept);
        }

        return (int)$intercept;
    }
}
