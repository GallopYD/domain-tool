<?php

namespace App\Utils;

class DomainUtil
{
    /**
     * 解析域名
     * @param $domain
     * @return array
     */
    public static function parse($domain)
    {
        $suffix = '';
        $name = strtolower($domain);
        $domainArr = explode('.', $name);
        $num = count($domainArr);
        if ($num == 2) {
            $name = $domainArr[0];
            $suffix = $domainArr[1];
        } elseif ($num > 2) {
            //判断后缀是否为二级域后缀
            if (in_array($domainArr[$num - 2] . '.' . $domainArr[$num - 1], config('app.second_domain_suffix'))) {
                $name = $domainArr[$num - 3];
                $suffix = $domainArr[$num - 2] . '.' . $domainArr[$num - 1];
            } else {
                $name = $domainArr[$num - 2];
                $suffix = $domainArr[$num - 1];
            }
        }
        return [$name, $suffix];
    }

    /**
     * 只获取顶级域名
     * @param $domain
     * @return string
     */
    public static function getTopDomain($domain)
    {
        $top_domain = '';
        $parse = self::parse($domain);
        if ($parse[0] && $parse[1]) {
            $top_domain = $parse[0] . '.' . $parse[1];
        }
        return $top_domain;
    }

    /**
     * 域名国际化编码
     * @param $domain
     * @return string
     */
    public static function punycode_encode($domain)
    {
        $puny_code = new PunyCodeUtil();
        return $puny_code->encode($domain);
    }

    /**
     * 域名国际化解码
     * @param $domain
     * @return string
     */
    public static function punycode_decode($domain)
    {
        $puny_code = new PunyCodeUtil();
        return $puny_code->decode($domain);
    }

    /**
     * 域名格式检查
     * @param $domain
     * @return bool
     */
    public static function checkFormat($domain)
    {
        if (preg_match("/^(([\x{4e00}-\x{9fa5}]|[a-zA-Z0-9-])+(\.[a-z]{2,5})?\.)+([a-z]|[\x{4e00}-\x{9fa5}]){2,10}$/ui", $domain)) {
            // 去掉-开头的域名
            if (substr($domain, 0, 1) != '-' && stripos($domain, '--') === FALSE) {
                return true;
            }
        }
        return false;
    }
}
