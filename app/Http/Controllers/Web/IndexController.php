<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;

class IndexController extends Controller
{
    public function index($type = 'qq')
    {
        $timestamp = time();
        $token = sha1(md5('tools_token' . $timestamp));
        return view('index', compact('type', 'timestamp', 'token'));
    }
}
