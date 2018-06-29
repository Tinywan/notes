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

/**
 * 支付接口
 */
Route::get("api/notify","api/Pay/notifyUrl");
Route::get("api/return","api/Pay/returnUrl");