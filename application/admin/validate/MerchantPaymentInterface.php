<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/7/10 16:36
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 描述信息
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\admin\validate;

use think\Validate;

class MerchantPaymentInterface extends Validate
{
    protected $rule = [
        'mch_id' => 'require',
        'payment_interface_id' => 'require',
        'payment_fee' => 'require',
    ];

    protected $message = [
        'mch_id.require' => '代付id不能为空',
        'payment_interface_id.require' => '商户id不能为空',
        'payment_fee.require' => '代付费率不能为空',
    ];
}