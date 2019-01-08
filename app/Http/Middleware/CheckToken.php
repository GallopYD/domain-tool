<?php

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use Carbon\Carbon;
use Closure;

class CheckToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $timestamp = $request->timestamp;
        $token = $request->token;
        if (!$timestamp || !$token) {
            throw new ApiException('必要参数缺失');
        }
        $time = Carbon::parse(date('Y-m-d H:i:s', $timestamp))->addHours(2);//有效期2小时
        $now = Carbon::now();
        if ($now->gt($time)) {
            throw new ApiException('页面已过期，请刷新重试');
        }
        $key = config('tool.token_key');
        if ($token != sha1(md5($key . $timestamp))) {
            throw new ApiException('非法访问');
        }
        return $next($request);
    }
}
