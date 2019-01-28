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

## 原理
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

## 代理
-  不使用代理：QQ管家查询及第三方查询结果不准确
- 使用代理：查询结果较为准确
  - 使用免费代理：https://proxy.357.im/ ，源码：[https://github.com/GallopYD/proxy-pool](https://github.com/GallopYD/proxy-pool)
  - 使用其他代理：修改 **app\Utils\ProxyUtil.php** 获取代理方法

## 配置

修改.env文件中，以下两处：

> $ vim .env

- 获取代理地址
```shell
PROXY_POOL_HOST=https://proxy.357.im/
```

- 微信测试号/服务号（微信接口频率限制：1000/10000 每天）

```shell
WECHAT_ACCOUNT=[{"app_id":"wx124d666666666666","app_secret":"8cd0b6f79d8008d0d265666666666666"}]
```

## 初始化(API文档)

> $ php artisan l5:gen

## 使用
- 前台：HOST
- API文档 ：HOST/api/doc