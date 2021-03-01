<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Http\Controllers\ApiController;
use App\Service\QQService;
use App\Service\WeChatService;
use App\Service\WhoisService;
use App\Utils\DomainUtil;
use Illuminate\Http\Request;

class ToolController extends ApiController
{

    /**
     * QQ拦截检测
     * @SWG\Post(
     *     path="/api/tools/qq",
     *     tags={"Tool"},
     *     @SWG\Parameter(name="domain",required=true,in="formData",type="string",description="域名"),
     *     @SWG\Response(response="200", description="")
     * )
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function qq(Request $request)
    {
        if (!$request->domain || !DomainUtil::checkFormat($request->domain)) {
            throw new ApiException("域名【{$request->domain}】格式不合法");
        }
        $service = new QQService();
        $intercept = $service->check($request->domain);
        return response()->json(['data' => compact('domain', 'intercept')]);
    }

    /**
     * 微信拦截检测
     * @SWG\Post(
     *     path="/api/tools/wechat",
     *     tags={"Tool"},
     *     @SWG\Parameter(name="domain",required=true,in="formData",type="string",description="域名"),
     *     @SWG\Response(response="200", description="")
     * )
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function wechat(Request $request)
    {
        if (!$request->domain || !DomainUtil::checkFormat($request->domain)) {
            throw new ApiException("域名【{$request->domain}】格式不合法");
        }
        $service = new WeChatService();
        $intercept = $service->check($request->domain);
        return response()->json(['data' => compact('domain', 'intercept')]);
    }

    /**
     * whois查询
     * @SWG\Post(
     *     path="/api/tools/whois",
     *     tags={"Tool"},
     *     @SWG\Parameter(name="domain",required=true,in="formData",type="string",description="域名"),
     *     @SWG\Parameter(name="fresh",required=false,in="formData",type="integer",default="0",description="1:立即刷新，0:不刷新"),
     *     @SWG\Response(response="200", description="whois信息")
     * )
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function whois(Request $request)
    {
        if (!$request->domain || !DomainUtil::checkFormat($request->domain)) {
            throw new ApiException("域名【{$request->domain}】格式不合法");
        }
        $service = new WhoisService();
        $data = $service->check($request->domain, $request->fresh);
        return response()->json($data);
    }
}
