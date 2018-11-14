<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/6/29 23:26
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 路由配置信息
 * '------------------------------------------------------------------------------------------------------------------*/

use  \think\facade\Route;

/**
 * 登录路由配置
 */
Route::any('/admin/login', 'index/auth/adminLogin');
Route::any('/merchant/login', 'index/auth/merchantLogin');
Route::any('/agents/login', 'index/auth/agentsLogin');
Route::get('user/:id','index/Index/userHello')->model(\app\common\model\User::class);

// 中间件路由配置,注册多个中间件
Route::rule('index/hello/:name','index/user/ruleLogin')
    ->middleware(['Check']);
