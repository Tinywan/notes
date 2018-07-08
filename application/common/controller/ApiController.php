<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/6/3 21:11
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 描述信息
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\common\controller;


class ApiController extends BaseController
{
    // 订单延迟key
    const ORDER_DELAY_KEY = 'QUEUES:DELAY:ORDER';

    // 支付异步key
    const PAY_NOTICE_KEY = 'QUEUES:PAY:NOTICE';
}
