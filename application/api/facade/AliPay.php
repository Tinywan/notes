<?php

/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/7/5 22:02
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 阿里支付面板
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\api\facade;


use think\Facade;

/**
 * @method \app\api\channel\AliPay gateWay() static 网关支付
 * @method bool scanCode() static 支付宝扫码
 * @method bool h5() static 支付宝h5
 * @method bool wap() static 支付宝wap
 */
class AliPay extends Facade
{
    protected static function getFacadeClass()
    {
        return 'app\api\channel\AliPay';
    }
}