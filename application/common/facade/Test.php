<?php

/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/7/5 20:55
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 描述信息
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\common\facade;


use think\Facade;

/**
 * @method \app\api\channel\AliPay gateWay() static 网关支付
 * @method bool scanCode() static 支付宝扫码
 * @method bool h5() static 支付宝h5
 * @method bool wap() static 支付宝wap
 */
class Test extends Facade
{
    protected static function getFacadeClass()
    {
        return 'app\api\channel\AliPay';
    }
}