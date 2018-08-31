<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

use \think\facade\Route;

Route::get("api/:version/token/get","api/:version.Token/getToken");
Route::get("api/:version/token/user","api/:version.Token/getUser");
Route::get("api/:version/test/index","api/auth.:version.Test/index");

/**
 * +----------------------------------------------------------------------支付接口
 */

// 网关 http://openapi.tinywan.com/v1/gateway.do
Route::post(":version/gateway.do","api/:version.Gateway/payDo");
Route::post(":version/gateway","api/:version.Gateway/payDoNew");

// 回调 http://openapi.tinywan.com/api/v1/return
Route::get("api/:version/return","api/:version.Gateway/returnUrl");

// 这里配置路由一定是post呀，坑啊
Route::rule("api/:version/notify","api/:version.Gateway/notifyUrl");

// App 登录 api/v1/login
Route::post("api/:version/login","api/:version.Auth/login");