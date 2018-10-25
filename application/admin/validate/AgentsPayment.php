<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/6/15 16:39
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 描述信息
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\admin\validate;

use think\Validate;

class AgentsPayment extends Validate
{
    protected $rule = [
        'agents_id'=>'require',
        'payment'=>'require',
        'agents_rate'=>'require',
        'channel_ids'=>'require',
        'channel_config_ids'=>'require',
        'status'=>'require',

    ];

    protected $message = [
        'agents_id.require'=>"代理商不能为空",
        'payment.require'=>"支付方式不能为空",
        'agents_rate.require'=>"代理费率不能为空",
        'channel_ids.require'=>"通道id组不能为空",
        'channel_config_ids.require'=>"渠道配置id组不能为空",
        'status.require'=>"开关状态不能为空",
    ];
}
