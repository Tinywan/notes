<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/6/3 21:11
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 支付基类控制器
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\common\controller;

use think\App;
use think\Controller;

class PayController extends Controller
{
    // 订单延迟key
    const ORDER_DELAY_KEY = 'QUEUES:DELAY:ORDER';

    // 支付异步key
    const PAY_NOTICE_KEY = 'QUEUES:PAY:NOTICE';

    public function __construct(App $app = null)
    {
        parent::__construct($app);
    }
}
