<?php

namespace App\Service;

use App\Utils\ProxyUtil;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * 360拦截检测
 * Class QiHooService
 * @package App\Service
 */
class QiHooService extends BaseService
{

    /**
     * 360域名检测【Beta】
     * @param $domain
     * @param $fresh
     * @param int $try
     * @return bool|int
     */
    public function check($domain, $fresh = false, $try = 1)
    {
        if ((!$intercept = Redis::get('intercept:360:' . $domain)) || $fresh) {
            //获取代理
            $proxy = ProxyUtil::getValidProxy();
            $intercept = $this->checkProcess($domain, $proxy);
            //查询失败重试
            if ($intercept == 0 && $try < 2) {
                return $this->check($domain, $fresh, ++$try);
            } elseif ($intercept) {
                //查询成功，检测结果缓存24小时
                Redis::setex('intercept:360:' . $domain, 24 * 60 * 60, $intercept);
            }
        }
        return $intercept;
    }

    /**
     * 360拦截查询
     * 先查ChinaZ再查WebScan
     * @param $domain
     * @param null $proxy
     * @return int
     */
    private function checkProcess($domain, $proxy = null)
    {
        $intercept = 0;
        try {
            $intercept = $this->checkViaChinaZ($domain, $proxy);
            Log::info("360拦截查询成功[域名：{$domain}][ChinaZ][代理：$proxy]：" . $intercept);
        } catch (\Exception $exception) {
            Log::info("360拦截查询失败[域名：{$domain}][ChinaZ][代理：$proxy]：" . $exception->getMessage());
            if ($exception->getMessage() == 'Undefined index: webstate') {
                try {
                    $intercept = $this->checkViaWebScan($domain, $intercept);
                    Log::info("360拦截查询成功[域名：{$domain}][WebScan][代理：$proxy]：" . $intercept);
                } catch (\Exception $e) {
                    Log::info("360拦截查询失败[域名：{$domain}][WebScan][代理：$proxy]：" . $e->getMessage());
                }
            }
        }
        return $intercept;
    }

    /**
     * 站长之家检测
     * @param $domain
     * @param null $proxy
     * @return int
     */
    private function checkViaChinaZ($domain, $proxy = null)
    {
        $client = new Client();
        $options = [
            'connect_timeout' => 5,
            'timeout' => 5,
        ];
        //是否使用代理
        if ($proxy) {
            $options['proxy'] = $proxy;
        }
        $url = 'http://tool.chinaz.com/webscan/?host=' . $domain;
        $response = $client->request('GET', $url, $options);
        $contents = $response->getBody()->getContents();
        //检测结果分析
        preg_match("/var str = (.*?)\;/is", $contents, $matches);
        $check_res = json_decode($matches[1], true);
        if ($check_res['webstate'] == 0) {
            $intercept = 1;
        } else if ($check_res['webstate'] == 1) {
            $intercept = 2;
        } else if ($check_res['webstate'] == 2) {
            $intercept = 2;
        } else if ($check_res['webstate'] == 3) {
            $intercept = 2;
        } else if ($check_res['webstate'] == 4) {
            $intercept = 1;
        } else {
            $intercept = 1;
        };
        return $intercept;
    }

    /**
     * WebScan 检测
     * @param $domain
     * @param null $proxy
     * @return int
     */
    private function checkViaWebScan($domain, $proxy = null)
    {
        $client = new Client();
        $options = [
            'connect_timeout' => 3,
            'timeout' => 3,
        ];
        //是否使用代理
        if ($proxy) {
            $options['proxy'] = $proxy;
        }
        $page_url = 'http://webscan.360.cn/index/checkwebsite?url=' . $domain;
        $page_response = $client->request('GET', $page_url, $options);
        $page_contents = $page_response->getBody()->getContents();
        //获取token timestamp
        preg_match("/&token=(.*?)&/is", $page_contents, $token_matches);
        preg_match("/&time=(.*?)\"/is", $page_contents, $time_matches);
        $token = $token_matches[1];
        $time = $time_matches[1];

        $check_url = 'http://webscan.360.cn/index/gettrojan';
        $options['form_params'] = [
            'url' => $domain,
            'time' => $time,
            'token' => $token
        ];
        $check_response = $client->request('POST', $check_url, $options);
        $check_contents = $check_response->getBody()->getContents();
        $data = json_decode($check_contents, true);
        //检测结果分析
        if ($data['state'] == 'ok') {
            if (isset($data['trojan'])) {
                $trojan = $data['trojan'];
                $type = isset($trojan['type']) ? $trojan['type'] : 0;
                $st = isset($trojan['st']) ? $trojan['st'] : 0;
                $sc = isset($trojan['sc']) ? $trojan['sc'] : 0;
                $ssc = isset($trojan['ssc']) ? $trojan['ssc'] : 0;
                $list = isset($trojan['list']) ? $trojan['list'] : null;
                if ($type == 60 && $st == 10 && $sc == 115 && $ssc == 1151) {
                    //遭恶意篡改
                    $intercept = 2;
                } else if ($type == 40 && $st == 50) {
                    //遭恶意篡改
                    $intercept = 2;
                } else if (($type == 60 && $st == 20) || ($type == 70)) {
                    //有挂马或恶意
                    $intercept = 2;
                } else if ($type == 60) {
                    //有虚假或欺诈
                    $intercept = 2;
                } else if ($type == 50 || ($list != null && count($list) > 0)) {
                    //有挂马或恶意
                    $intercept = 2;
                } else {
                    //正常
                    $intercept = 1;
                }
            } else {
                //安全网站
                $intercept = 1;
            }
        } else {
            $intercept = 0;//检测失败
        }
        return $intercept;
    }

}