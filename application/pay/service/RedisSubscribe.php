<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/6/30 15:23
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc:
 *      1、生成订单30分钟未支付，则自动取消
 * |    2、生成订单60秒后,给用户发短信
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\pay\service;


use app\common\model\Merchant;
use app\common\model\Order;
use app\common\queue\MultiTask;
use redis\BaseRedis;
use think\facade\Log;

class RedisSubscribe
{
    public function sub()
    {
        $msg = "Tinywan";
        $redis = BaseRedis::location();
        $redis->setOption(\Redis::OPT_READ_TIMEOUT, -1);
        $redis->psubscribe(array('__keyevent@0__:expired'), function ($redis, $pattern, $chan, $orderNo) use ($msg) {
            Log::error(get_current_date() . $msg . ' 订阅成功的订单号为: ' . $orderNo);
            $this->createOrderSendCode($orderNo);
        });
    }

    /**
     * 生成订单60秒后,给用户发短信
     * 由于短息你叫昂贵我这里使用邮件代替了
     * @param $orderNo
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function createOrderSendCode($orderNo)
    {
        $orderInfo = Order::where('order_no', '=', $orderNo)->find();
        $merchantInfo = Merchant::get($orderInfo->mch_id);
        Log::error(get_current_date() . ' 生成订单60秒后,给用户发短信，订单号为：' . $orderNo);
        // 发送一份邮件
        $taskType = MultiTask::EMAIL;
        $data = [
            'email' => $merchantInfo->email,
            'title' => "订单号: " . $orderNo,
            'content' => "让您做一个电商平台，您如何设置一个在买家下订单后的”第60秒“发短信通知卖家发货 " . rand(11111, 999999)
        ];
        $res = multi_task_Queue($taskType, $data);
        if ($res !== false) {
            Log::error(get_current_date() . ' 短信 Success ');
        } else {
            Log::error(get_current_date() . ' 短信 Error ');
        }
    }

    /**
     * 生成订单30分钟未支付，则自动取消
     * @param $orderNo
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function orderAutoCancel($orderNo)
    {
        $orderInfo = Order::where('order_no', '=', $orderNo)->find();
        Log::error(get_current_date() . ' 11订单信息:' . $orderNo);
        Log::error(get_current_date() . ' 22订单信息:' . json_encode($orderInfo));
    }
}