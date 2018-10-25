<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/6/19 11:49
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 描述信息
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\admin\validate;


use think\Validate;

class Merchant extends Validate
{
    protected $rule = [
        'username' => 'require|unique:merchant',
        'phone' => 'require',
        'email' => 'require|email',
        'status' => 'require',
        'is_auth' => 'require',
        'cash_status' => 'require',
    ];

    protected $message = [
        'username.require'=>'用户名不能为空',
        'username.unique'=>'用户名已经存在',
        'phone.require'=>'电话不能为空',
        'email.require'=>'邮箱不能为空',
        'email.email'=>'邮箱格式错误',
        'status.require'=>'状态是必须的',
    ];
}