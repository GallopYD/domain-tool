<?php

namespace App\Service;

use App\Utils\ProxyUtil;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * QQ拦截检测
 * Class QQService
 * @package App\Service
 */
class QQService extends BaseService
{

    /**
     * QQ域名检测
     * @param $domain
     * @param $fresh
     * @param int $try
     * @return bool|int
     */
    public function check($domain, $fresh = false, $try = 1)
    {
        if ((!$intercept = Redis::get('intercept:qq:' . $domain)) || $fresh) {
            //获取代理
            $proxy = ProxyUtil::getValidProxy();
            $intercept = $this->checkViaGuanJia($domain, $proxy);
            //查询失败重试
            if ($intercept == 0 && $try < 3) {
                return $this->check($domain, $fresh, ++$try);
            } elseif ($intercept == 0) {
                //短链接查询失败，查询第三方
                $intercept = $this->checkViaAdopt($domain, $proxy);
            } elseif ($intercept) {
                Redis::setex('intercept:qq:' . $domain, 24 * 60 * 60, $intercept);
            }
        }
        return $intercept;
    }

    /**
     * 腾讯电脑管家检测
     * @param $domain
     * @param null $proxy
     * @return int
     */
    private function checkViaGuanJia($domain, $proxy = null)
    {
        try {
            $client = new Client();
            $options = [
                'headers' => ['Referer' => 'https://guanjia.qq.com'],
                'connect_timeout' => 5,
                'timeout' => 5,
            ];
            //是否使用代理
            if ($proxy) {
                $options['proxy'] = $proxy;
            }
            $url = 'https://cgi.urlsec.qq.com/index.php?m=check&a=check&url=http://' . $domain;
            $response = $client->request('GET', $url, $options);
            $contents = $response->getBody()->getContents();
            $result = json_decode(substr($contents, 1, strlen($contents) - 2), true);//去除首位括号
            //whitetype 1：安全性未知 2：危险网站 3：安全网站
            if ($result['data']['results']['whitetype'] == 2) {
                $intercept = 2;
            } else {
                $intercept = 1;
            }
            Log::info("QQ拦截查询成功[电脑管家][域名：{$domain}][代理：$proxy]：" . $intercept);
        } catch (\Exception $exception) {
            $intercept = 0;//检测失败
            Log::info("QQ拦截查询失败[电脑管家][域名：{$domain}][代理：$proxy]：" . $exception->getMessage());
        }
        return $intercept;
    }

    /**
     * 微信第三方检测
     * @param $domain
     * @param null $proxy
     * @return int
     */
    private function checkViaAdopt($domain, $proxy = null)
    {
        $intercept = 0;
        try {
            $intercept = $this->checkViaYuMingJianCe($domain, $proxy);
            Log::info("QQ拦截查询成功[yumingjiance.net][域名：{$domain}]：" . $intercept);
        } catch (\Exception $exception) {
            Log::info("QQ拦截查询失败[yumingjiance.net][域名：{$domain}][代理：$proxy]：" . $exception->getMessage());
        }
        return $intercept;
    }

    /**
     * yumingjiance.net 微信拦截检测
     * @param $domain
     * @param null $proxy
     * @return int
     * @throws \Exception
     */
    private function checkViaYuMingJianCe($domain, $proxy = null)
    {
        $client = new Client();
        $options = [
            'connect_timeout' => 5,
            'timeout' => 5,
            'headers' => ['X-Requested-With' => 'XMLHttpRequest'],
        ];
        //是否使用代理
        if ($proxy) {
            $options['proxy'] = $proxy;
        }
        $url = 'http://www.yumingjiance.net/index.php?s=/index/ck_qq&domain=' . $domain;
        $response = $client->request('GET', $url, $options);
        $contents = $response->getBody()->getContents();
        $res = json_decode($contents, true);
        if ($res && $res['status'] === 0) {
            $intercept = 2;
        } elseif ($res && $res['status'] === 1) {
            $intercept = 1;
        } else {
            throw new \Exception('Exceeding times');
        }
        return $intercept;
    }

}