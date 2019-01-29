<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;

class IndexController extends Controller
{
    const TYPE_QQ = 'qq';
    const TYPE_360 = '360';
    const TYPE_WECHAT = 'wechat';

    public function index($type = self::TYPE_WECHAT)
    {
        $timestamp = time();
        $key = config('tool.token_key');
        $token = sha1(md5($key . $timestamp));
        return view('index', compact('type', 'timestamp', 'token'));
    }
}
