<?php

namespace App\Service;

use App\Utils\ProxyUtil;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * 微信拦截检测
 * Class WeChatService
 * @package App\Service
 */
class WeChatService extends BaseService
{
    private $config;

    public function __construct()
    {
        //获取测试号配置
        $this->config = $this->getConfig();
    }

    /**
     * 微信域名检测
     * @param $domain
     * @param $fresh
     * @param int $try
     * @return bool|int
     */
    public function check($domain, $fresh = false, $try = 1)
    {
        if ((!$intercept = Redis::get('intercept:wecaht:' . $domain)) || $fresh) {

            //优先短链接检测
            $intercept = $this->checkViaShortUrl($domain, null);

            if ($intercept == 0 && $try < 2) {
                //失败重试
                return $this->check($domain, $fresh, ++$try);
            } elseif ($intercept == 0) {
                //短链接查询失败，查询第三方
                $proxy = ProxyUtil::getValidProxy();
                $intercept = $this->checkViaAdopt($domain, $proxy);
            } elseif ($intercept) {
                //查询成功，检测结果缓存24小时
                Redis::setex('intercept:wecaht:' . $domain, 24 * 60 * 60, $intercept);
            }
        }

        return $intercept;
    }

    /**
     * 微信域名检测
     * @param $domain
     * @param null $proxy
     * @return int intercept：0查询失败 1正常 2拦截
     */
    private function checkViaShortUrl($domain, $proxy = null)
    {
        $intercept = 0;
        $short_url = '';
        try {
            //获取AccessToken
            $access_token = $this->getAccessToken();
            //生成短链接
            $short_url = $this->getShortUrl($access_token, $domain, $proxy);
            //短链接检测
            if ($res = $this->checkShortUrl($short_url)) {
                $intercept = 1;
            } else {
                $intercept = 2;
            }
            Log::info("微信拦截查询成功[域名：$domain][短链接:$short_url]：" . $intercept);
        } catch (\Exception $exception) {
            Log::info("微信拦截查询失败[域名：$domain][短链接:$short_url]：" . $exception->getMessage());
        }

        return $intercept;
    }

    /**
     * 获取Access_Token
     * @return mixed
     */
    private function getAccessToken()
    {
        $app_id = $this->config['app_id'];
        if (!$access_token = Redis::get('wechat:access_token:' . $app_id)) {
            $client = new Client();
            $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $this->config['app_id'] . '&secret=' . $this->config['app_secret'];
            $response = $client->request('GET', $url, [
                'connect_timeout' => 3,
                'timeout' => 3,
            ]);
            $contents = $response->getBody()->getContents();
            $data = json_decode($contents, true);
            $access_token = $data['access_token'];
            Redis::setex('wechat:access_token:' . $app_id, 7100, $access_token);//微信access_token有效期为7200s
        }
        return $access_token;
    }

    /**
     * 获取微信配置
     * @return mixed|null
     */
    private function getConfig()
    {
        $accounts = config('tool.wechat_account');
        foreach ($accounts as $account) {
            $limit = Redis::get('wechat:account:' . $account['app_id']);
            if (!$limit || $limit < 1000) {
                return $account;
            }
        }
        return null;
    }

    /**
     * 获取短链接
     * @param $access_token
     * @param $domain
     * @param $proxy
     * @return mixed
     * @throws \Exception
     */
    private function getShortUrl($access_token, $domain, $proxy)
    {
        $client = new Client();
        $url = "https://api.weixin.qq.com/cgi-bin/shorturl?access_token=$access_token";
        $options = [
            'json' => [
                'action' => 'long2short',
                'long_url' => 'http://' . $domain,
            ],
            'connect_timeout' => 3,
            'timeout' => 3,
        ];
        //是否使用代理
        if ($proxy) {
            $options['proxy'] = $proxy;
        }
        $response = $client->request('POST', $url, $options);
        $contents = $response->getBody()->getContents();
        //AccessToken非法
        if (strpos($contents, 'access_token is invalid') !== false) {
            Redis::del('wechat:access_token:' . $this->config['app_id']);
            throw new \Exception('access_token is invalid');
        } else {
            $data = json_decode($contents, true);
            $short_url = $data['short_url'];

            //接口频次限制
            $this->setInterfaceLimit();

            return $short_url;
        }
    }

    /**
     * 短链接拦截检测
     * @param $url
     * @return bool
     */
    private function checkShortUrl($url)
    {
        try {
            $client = new Client();
            $response = $client->request('GET', $url, [
                'connect_timeout' => 2,
                'timeout' => 2,
            ]);
            $contents = $response->getBody()->getContents();
            if (strpos($contents, '已停止访问该网页')) {
                return false;
            } else {
                return true;
            }
        } catch (\Exception $exception) {
            //网站无法访问 = 未拦截
            return true;
        }
    }

    /**
     * 接口频次限制
     * 公众号短链接接口：1000次/天
     */
    private function setInterfaceLimit()
    {
        $app_id = $this->config['app_id'];
        if (!$count = Redis::get('wechat:account:' . $app_id)) {
            //当天有效
            $expire = mktime(23, 59, 59) - mktime(date('H'), date('i'), date('s'));
            Redis::setex('wechat:account:' . $app_id, $expire, 1);
        } else {
            Redis::incr('wechat:account:' . $app_id);
        }
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
            $intercept = $this->checkViaWeiXinClup($domain, $proxy);
            Log::info("微信拦截查询成功[域名：{$domain}][weixinclup.com][代理：$proxy]：" . $intercept);
        } catch (\Exception $exception) {
            Log::info("微信拦截查询失败[域名：{$domain}][weixinclup.com][代理：$proxy]：" . $exception->getMessage());
            if ($exception->getMessage() == 'Exceeding times') {
                try {
                    $intercept = $this->checkViaDingXsd($domain, $intercept);
                    Log::info("微信拦截查询成功[域名：{$domain}][dingxsd.com][代理：$proxy]：" . $intercept);
                } catch (\Exception $e) {
                    Log::info("微信拦截查询失败[域名：{$domain}][dingxsd.com][代理：$proxy]：" . $exception->getMessage());
                }
            }
        }
        return $intercept;
    }

    /**
     * weixinclup.com 微信拦截检测
     * @param $domain
     * @param null $proxy
     * @return int
     * @throws \Exception
     */
    private function checkViaWeiXinClup($domain, $proxy = null)
    {
        $client = new Client();
        $options = [
            'connect_timeout' => 5,
            'timeout' => 5,
            'headers' => ['X-Requested-With' => 'XMLHttpRequest'],
            'form_params' => ['url' => $domain]
        ];
        //是否使用代理
        if ($proxy) {
            $options['proxy'] = $proxy;
        }
        $url = 'http://api.weixinclup.com/index/checkurl.html';
        $response = $client->request('POST', $url, $options);
        $contents = $response->getBody()->getContents();
        $res = json_decode($contents, true);
        if ($res && $res['errcode'] === 0) {
            $intercept = 2;
        } elseif ($res && $res['errcode'] === 1) {
            $intercept = 1;
        } else {
            throw new \Exception('Exceeding times');
        }
        return $intercept;
    }

    /**
     * dingxsd.com 微信拦截检测
     * @param $domain
     * @param null $proxy
     * @return int
     * @throws \Exception
     */
    private function checkViaDingXsd($domain, $proxy = null)
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
        $url = 'http://weixin.dingxsd.com/index.php?s=/index/ck_blacklist&domain=' . $domain;
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