<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/6/14 20:59
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 描述信息
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\index\controller;


use think\Controller;
use think\facade\Log;
use Yansongda\Pay\Pay;

class PayController extends Controller
{
    public function index()
    {
        $order = [
          'out_trade_no' => rand(11111,99999).time(),
          'total_amount' => rand(11,99),
          'subject' => '商品测试001',
        ];
        Log::error('-----------'.json_encode($order));
        $alipay = Pay::alipay(config('pay.alipay'))->web($order);
        return $alipay->send();
    }

    public function test()
    {
        var_dump(file_get_contents('../logs/123.txt'));
    }

    public function testRedis()
    {
        var_dump(config('redis.message'));
        $redis = location_redis();
        $redis->set("UserName",'Tinywan11111');
        halt($redis);
    }

    public function testCurl()
    {
        $url = 'https://www.tinywan.com/api/return';
        $res = curl_request($url);
        halt($res);
    }
}