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

use app\api\channel\AliPay;
use app\api\facade\WeChat;
use app\api\service\PayService;
use app\common\controller\PayController;
use think\facade\App;
use think\facade\Log;
use Yansongda\Pay\Pay;

class IndexController extends PayController
{
    public function index()
    {
        $order = [
          'out_trade_no' => rand(11111, 99999) . time(),
          'total_amount' => rand(11, 99),
          'subject' => '商品测试001' . rand(11111111, 888888888888),
        ];
        Log::error('-----------' . json_encode($order));
        $alipay = Pay::alipay(config('pay.alipay'))->web($order);
        return $alipay->send();
    }

    public function test(App $app)
    {
        $object = $app->invokeClass(AliPay::class);
        halt($object->gateWay());
    }

    public function test1()
    {
        $object = WeChat::gateWay();
        halt($object);
    }

    public function test34(){
        $channelObj = App::invokeClass(PayService::class);
        halt($channelObj);
    }
}
