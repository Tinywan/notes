<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/6/15 15:20
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 描述信息
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\admin\validate;

use think\Validate;

class AgentsChannelConfig extends Validate
{
    protected $rule = [
        'agents_id'=>'require',
        'channel'=>'require',
        'channel_merchant_id'=>'require',
        'status'=>'require',
    ];

    protected $message = [
        'agents_id.require'=>"代理商不能为空",
        'channel.require'=>"渠道不能为空",
        'channel_merchant_id.require'=>"渠道商户号配置id不能为空",
        'status.require'=>"状态",
    ];
}