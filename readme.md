# DomainTool

域名工具

[![](https://img.shields.io/badge/Powered%20by-GallopYD-green.svg)](https://357.im/)
[![GitHub contributors](https://img.shields.io/github/contributors/GallopYD/domain-tool.svg)](https://github.com/GallopYD/domain-tool/graphs/contributors)
[![](https://img.shields.io/badge/language-PHP-blue.svg)](https://github.com/GallopYD/domain-tool)

## 功能

* QQ拦截查询
* 微信拦截查询
* 360拦截查询<sup>beta</sup>
* Whois查询  

## 解决方案
- QQ：调用**腾讯电脑管家**域名查询接口，失败则调用**第三方**接口
- 微信：通过**公众号**（或测试号）生成短链接，再访问短链接测试访问结果，失败则调用**第三方**接口
- 360：**360网站安全监测**（不稳定），失败则爬取**站长之家**网站安全检测
- whois：使用linux下的 **whois/jwhois** 或其他whois插件

## 环境

- PHP >= 7.0
- php_redis 扩展
- Laravel 5.5

## 安装

> $ git clone https://github.com/GallopYD/domain-tool.git

> $ cd domain-tool && composer install

> $ cp .env.example .env

> $ php artisan key:gen

> $ yum install -y jwhois


## 配置

#### 缓存
**默认**开启缓存，缓存时间为24小时，配置如下：
```shell
$ vim .env
TOOL_CACHE_ENABLE=true
```

####  代理
**默认**不使用代理，查询结果可能不准确。如需使用代理，配置env文件（代理格式为TXT）：
```shell
$ vim .env
PROXY_HOST=http://api.xdaili.cn/xdaili-api/greatRecharge/getGreatIp...
```
如需自行部署代理，可参考[免费代理池](https://github.com/GallopYD/proxy-pool)

#### 微信
微信测试号/服务号（微信接口频率限制：1000/10000 每天）
```shell
WECHAT_ACCOUNT=[{"app_id":"wx124d666666666666","app_secret":"8cd0b6f79d8008d0d265666666666666"}]
```

## 初始化(API文档)

> $ php artisan l5:gen

## 使用

- 前台：HOST
- API文档 ：HOST/api/doc