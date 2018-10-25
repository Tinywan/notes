<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/6/21 13:34
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 描述信息
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\admin\validate;


use think\Validate;

class AgentsChannelPayment extends Validate
{
    protected $rule = [
        'agents_id'=>'require',
        'payment_id'=>'require',
        'agents_rate'=>'require',
        'channel_id'=>'require',
        'status'=>'require',

    ];

    protected $message = [
        'agents_id.require'=>"代理商不能为空",
        'payment_id.require'=>"支付方式不能为空",
        'agents_rate.require'=>"代理费率不能为空",
        'channel_id.require'=>"渠道不能为空",
        'status.require'=>"开关状态不能为空",
    ];
}