<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/6/14 13:21
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 描述信息
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\admin\validate;

use app\common\validate\BaseValidate;

class Agents extends BaseValidate
{
    protected $rule = [
        'agents_name' => 'require|unique:agents',
        'phone' => 'require|isMobile',
        'email' => 'require|email',
        'status' => 'require',
        'transfer_rate' => 'require',
    ];

    protected $message = [
        'agents_name.require'=>'用户名不能为空',
        'agents_name.unique'=>'用户名已经存在',
        'phone.require'=>'电话不能为空',
        'phone.isMobile'=>'电话格式错误',
        'email.require'=>'邮箱不能为空',
        'email.email'=>'邮箱格式错误',
        'status.require'=>'状态是必须的',
        'transfer_rate.require'=>'转账费率不能为空',
    ];
}