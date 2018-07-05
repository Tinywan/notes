<?php
/** .-----------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |-------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/6/2 13:41
 * |  Mail: Overcome.wan@Gmail.com
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\pay\controller;

use app\common\controller\BasePayController;
use think\facade\Log;
use Yansongda\Pay\Pay;

class IndexController extends BasePayController
{
    public function index()
    {
        $order = [
            'out_trade_no' => rand(11111,99999).time(),
            'total_amount' => rand(11,99),
            'subject' => '商品测试001'.rand(11111111,888888888888),
        ];
        Log::error('-----------'.json_encode($order));
        $alipay = Pay::alipay(config('pay.alipay'))->web($order);
        return $alipay->send();
    }
}
