<?php

namespace App\Service;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * Class WeChatService
 * @package App\Service
 */
class WeChatService extends BaseService
{
    private $config;

    /**
     * 微信拦截检测
     *
     * @param $domain
     * @return int
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function check($domain)
    {
        if (!$intercept = Redis::get("intercept:wecaht:{$domain}")) {
            if (!$intercept = $this->checkByHeaders($domain)) {
                $intercept = $this->checkByShortUrl($domain);
            }
        }
        // 检测结果缓存
        if ($intercept && $this->cache_enable) {
            Redis::setex("intercept:wecaht:{$domain}", 24 * 60 * 60, $intercept);
        }
        return (int)$intercept;
    }

    /**
     * 响应头检测
     *
     * @param $domain
     * @return int
     */
    public function checkByHeaders($domain)
    {
        try {
            $headers = get_headers("http://mp.weixinbridge.com/mp/wapredirect?url=http://{$domain}");
            $intercept = $headers[6] != "Location: http://{$domain}" ? 2 : 1;
        } catch (\Exception $exception) {
            $intercept = 1;
        }
        return $intercept;
    }

    /**
     * 短连接检测
     *
     * @param $domain
     * @return int
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function checkByShortUrl($domain)
    {
        $intercept = 0;
        try {
            $access_token = $this->getAccessToken();// 获取AccessToken
            $short_url = $this->getShortUrl($access_token, $domain);// 生成短链接
            $intercept = $this->checkShortUrl($short_url) ? 1 : 2;// 短链接检测
            Log::info("微信查询成功[$domain]", [$intercept, $short_url]);
        } catch (\Exception $exception) {
            Log::info("微信查询失败[$domain]：", [$exception->getMessage()]);
        }
        return $intercept;
    }

    /**
     * 获取AccessToken
     *
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function getAccessToken()
    {
        // 公众号配置
        foreach (config('tool.wechat_account') as $account) {
            $limit = Redis::get("wechat:account:{$account['app_id']}");
            if (!$limit || $limit < 1000) {
                $this->config = $account;
                break;
            }
        }

        if (!$access_token = Redis::get("wechat:access_token:{$this->config['app_id']}")) {
            $client = new Client();
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$this->config['app_id']}&secret={$this->config['app_secret']}";
            $response = $client->request('GET', $url, ['connect_timeout' => 3, 'timeout' => 3]);
            $contents = $response->getBody()->getContents();
            $data = json_decode($contents, true);
            $access_token = $data['access_token'];
            Redis::setex("wechat:access_token:{$this->config['app_id']}", 7100, $access_token);// access_token 缓存
        }
        return $access_token;
    }

    /**
     * 获取短链接
     *
     * @param $access_token
     * @param $domain
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function getShortUrl($access_token, $domain)
    {
        if (!$short_url = Redis::get("short_url:{$domain}")) {
            $client = new Client();
            $url = "https://api.weixin.qq.com/cgi-bin/shorturl?access_token={$access_token}";
            $options = [
                'json' => ['action' => 'long2short', 'long_url' => "http://{$domain}"],
                'connect_timeout' => 3,
                'timeout' => 3,
            ];
            $response = $client->request('POST', $url, $options);
            $contents = $response->getBody()->getContents();

            // AccessToken非法
            if (strpos($contents, 'access_token is invalid') !== false) {
                Redis::del("wechat:access_token:{$this->config['app_id']}");
                throw new \Exception('access_token is invalid');
            } else {
                $data = json_decode($contents, true);
                $short_url = $data['short_url'];
                $this->setApiLimit();// 接口频次限制
                Redis::setex('short_url:' . $domain, 24 * 60 * 60 * 90, $short_url);// 短链接缓存90天
            }
        }

        return $short_url;
    }

    /**
     * 短链接拦截检测
     *
     * @param $url
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function checkShortUrl($url)
    {
        try {
            $client = new Client();
            $response = $client->request('GET', $url, ['connect_timeout' => 1, 'timeout' => 1]);
            $contents = $response->getBody()->getContents();
            $res = strpos($contents, '已停止访问该网页') ? false : true;
        } catch (\Exception $exception) {
            $res = true;// 网站无法访问 = 未拦截
        }
        return $res;
    }

    /**
     * 接口频次限制
     */
    private function setApiLimit()
    {
        if (!$count = Redis::get("wechat:account:{$this->config['app_id']}")) {
            $expire = mktime(23, 59, 59) - mktime(date('H'), date('i'), date('s'));// 当天有效
            Redis::setex("wechat:account:{$this->config['app_id']}", $expire, 1);
        } else {
            Redis::incr("wechat:account:{$this->config['app_id']}");
        }
    }
}
