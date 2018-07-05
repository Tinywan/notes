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
 * @method \app\api\channel\WeChat gateWay() static 网关支付
 * @method bool scanCode() static 扫码
 * @method bool h5() static h5
 * @method bool wap() static wap
 */
class WeChat extends Facade
{
    protected static function getFacadeClass()
    {
        return 'app\api\channel\WeChat';
    }
}