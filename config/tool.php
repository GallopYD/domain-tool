<?php

return [

    //开启代理
    'proxy_enable' => env('PROXY_ENABLE', false),

    //代理地址
    'proxy_host' => env('PROXY_HOST'),

    //微信账号
    //格式：[{"app_id":"wx124d8952d3123456","app_secret":"8cd0b6f79d8008d0d265e5b0e3123456"}]
    'wechat_account' => json_decode(env('WECAHT_ACCOUNT'), true),

    //whois查询命令
    'whois_command' => env('WHOIS_COMMAND', 'whois'),

    //token key
    'token_key' => env('TOKEN_KEY', 'token_key'),

    //查询结果缓存
    'cache_enable' => env('TOOL_CACHE_ENABLE', true),

];
