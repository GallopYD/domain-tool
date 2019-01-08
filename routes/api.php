<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group([
    'namespace' => 'Api',
    'prefix' => '',
    'as' => 'api',
    'limit' => 300,
    'expires' => 5
], function () {
    Route::get('tools/token', 'ToolController@getToken');
    Route::group([
        'middleware' => 'check.token'
    ], function () {
        Route::post('tools/qq', 'ToolController@qq');//QQ拦截查询
        Route::post('tools/wechat', 'ToolController@weChat');//微信拦截查询
        Route::post('tools/360', 'ToolController@qiHoo');//360拦截查询
        Route::post('tools/whois', 'ToolController@whois');//whois查询
    });
});
