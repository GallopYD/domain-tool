<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;

class IndexController extends Controller
{
    public function index($type = 'qq')
    {
        $timestamp = time();
        $key = config('tool.token_key');
        $token = sha1(md5($key . $timestamp));
        return view('index', compact('type', 'timestamp', 'token'));
    }
}
