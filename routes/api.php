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
    'as' => 'api',
    'limit' => 300,
    'expires' => 5
], function () {
    Route::post('tools/qq', 'ToolController@qq');
    Route::post('tools/wechat', 'ToolController@wechat');
    Route::post('tools/whois', 'ToolController@whois');
});
