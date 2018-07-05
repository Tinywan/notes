<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/7/5 21:02
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 描述信息
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\api\channel;


class WeChat
{
    public function gateWay()
    {
        return '网关支付 ' . __METHOD__;
    }

    /**
     * 扫码支付
     * @return string
     */
    public function scanCode()
    {
        return '扫码支付 ' . __METHOD__;
    }

    public function h5()
    {
        return 'h5支付' . __METHOD__;
    }

    public function wap()
    {
        return '支付宝wap ' . __METHOD__;
    }
}