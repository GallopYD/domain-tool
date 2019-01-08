<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Http\Controllers\ApiController;
use App\Service\QiHooService;
use App\Service\QQService;
use App\Service\WeChatService;
use App\Service\WhoisReverseService;
use App\Service\WhoisService;
use App\Utils\DomainUtil;
use GuzzleHttp\Client;
use Illuminate\Http\Request;

class ToolController extends ApiController
{

    /**
     * QQ拦截检测
     * @SWG\Post(
     *     path="/api/tools/qq",
     *     tags={"Tool"},
     *     @SWG\Parameter(name="domain",required=true,in="formData",type="string",description="域名"),
     *     @SWG\Parameter(name="fresh",required=false,in="formData",type="integer",default="0",description="1:立即刷新，0:不刷新"),
     *     @SWG\Parameter(name="timestamp",required=false,in="formData",type="string",description="时间戳"),
     *     @SWG\Parameter(name="token",required=false,in="formData",type="string",description="token"),
     *     @SWG\Response(response="200", description="")
     * )
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function qq(Request $request)
    {
        $this->validate($request, [
            'domain' => 'required'
        ], [
            'domain.required' => '域名不能为空'
        ]);
        $domain = $request->domain;
        $fresh = $request->fresh;
        if (!DomainUtil::checkFormat($domain)) {
            throw new ApiException('域名格式不合法');
        }
        $service = new QQService();
        $intercept = $service->check($domain, $fresh);
        return response()->json(['data' => compact('domain', 'intercept')]);
    }

    /**
     * 微信拦截检测
     * @SWG\Post(
     *     path="/api/tools/wechat",
     *     tags={"Tool"},
     *     @SWG\Parameter(name="domain",required=true,in="formData",type="string",description="域名"),
     *     @SWG\Parameter(name="fresh",required=false,in="formData",type="integer",default="0",description="1:立即刷新，0:不刷新"),
     *     @SWG\Parameter(name="timestamp",required=false,in="formData",type="string",description="时间戳"),
     *     @SWG\Parameter(name="token",required=false,in="formData",type="string",description="token"),
     *     @SWG\Response(response="200", description="")
     * )
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function weChat(Request $request)
    {
        $this->validate($request, [
            'domain' => 'required'
        ], [
            'domain.required' => '域名不能为空'
        ]);
        $domain = $request->domain;
        $fresh = $request->fresh;
        if (!DomainUtil::checkFormat($domain)) {
            throw new ApiException('域名格式不合法');
        }
        $service = new WeChatService();
        $intercept = $service->check($domain, $fresh);
        return response()->json(['data' => compact('domain', 'intercept')]);
    }

    /**
     * 360拦截检测
     * @SWG\Post(
     *     path="/api/tools/360",
     *     tags={"Tool"},
     *     @SWG\Parameter(name="domain",required=true,in="formData",type="string",description="域名"),
     *     @SWG\Parameter(name="fresh",required=false,in="formData",type="integer",default="0",description="1:立即刷新，0:不刷新"),
     *     @SWG\Parameter(name="timestamp",required=false,in="formData",type="string",description="时间戳"),
     *     @SWG\Parameter(name="token",required=false,in="formData",type="string",description="token"),
     *     @SWG\Response(response="200", description="whois信息")
     * )
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function qiHoo(Request $request)
    {
        $this->validate($request, [
            'domain' => 'required'
        ], [
            'domain.required' => '域名不能为空'
        ]);
        $domain = $request->domain;
        $fresh = $request->fresh;
        if (!DomainUtil::checkFormat($domain)) {
            throw new ApiException('域名格式不合法');
        }
        $service = new QiHooService();
        $intercept = $service->check($domain, $fresh);
        return response()->json(['data' => compact('domain', 'intercept')]);
    }

    /**
     * whois查询
     * @SWG\Post(
     *     path="/api/tools/whois",
     *     tags={"Tool"},
     *     @SWG\Parameter(name="domain",required=true,in="formData",type="string",description="域名"),
     *     @SWG\Parameter(name="fresh",required=false,in="formData",type="integer",default="0",description="1:立即刷新，0:不刷新"),
     *     @SWG\Parameter(name="timestamp",required=false,in="formData",type="string",description="时间戳"),
     *     @SWG\Parameter(name="token",required=false,in="formData",type="string",description="token"),
     *     @SWG\Response(response="200", description="whois信息")
     * )
     */
    public function whois(Request $request)
    {
        $this->validate($request, [
            'domain' => 'required'
        ], [
            'domain.required' => '请输入查询的域名'
        ]);
        if (!DomainUtil::checkFormat($request->domain)) {
            throw new ApiException('域名格式不合法');
        }
        $service = new WhoisService();
        $data = $service->check($request->domain, $request->fresh);
        return response()->json($data);
    }

    /**
     * Token获取
     * @SWG\Get(
     *     path="/api/tools/token",
     *     tags={"Tool"},
     *     @SWG\Response(response="200", description="")
     * )
     */
    public function getToken()
    {
        $timestamp = time();
        $key = config('tool.token_key');
        $token = sha1(md5($key . $timestamp));
        return response()->json(['data' => compact('timestamp', 'token')]);
    }
}
