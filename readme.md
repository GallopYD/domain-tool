# DomainTool

域名工具：微信域名拦截检测、QQ域名拦截检测：http://eson.vip ，查询有缓存，如需实时查询请自行部署。

## 功能

* 微信拦截查询
* QQ拦截查询
* Whois查询  

## 解决方案

- 微信：通过 **官方API** 查询，或者 **公众号短链接** 查询
- Q Q：通过 **腾讯电脑管家** API查询
- whois：通过 **whois/jwhois** 查询

具体可查看 **源码** 或 **[博客](https://blog.csdn.net/qq292913477/article/details/86572412)**

## 环境

- PHP >= 7.0
- php_redis 扩展
- Laravel 5.5

## 安装

```
git clone https://github.com/GallopYD/domain-tool.git
cd domain-tool && composer install
cp .env.example .env
php artisan key:gen

# 不使用 whois 查询，无需安装
yum install -y jwhois
```


## 配置

```
vim .env
```
```
# 默认开启缓存（24小时）
TOOL_CACHE_ENABLE=true

# 微信测试号/服务号（不使用短连接查询，无需配置）
WECHAT_ACCOUNT=[{"app_id":"wx124d666666666666","app_secret":"8cd0b6f79d8008d0d265666666666666"}]
```

## 初始化

```
php artisan l5:gen
```
