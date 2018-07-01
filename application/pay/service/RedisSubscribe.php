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
    // 支付类型
    const PAY = 'PAY';

    // 回调处理
    const NOTICE = 'NOTICE';

    /**
     * orderDelayMessage
     */
    public function orderDelayMessage()
    {
        $msg = "Tinywan";
        $redis = BaseRedis::location();
        $redis->setOption(\Redis::OPT_READ_TIMEOUT, -1);
        $redis->psubscribe(array('__keyevent@0__:expired'), function ($redis, $pattern, $chan, $orderNo) use ($msg) {
            Log::error('回调的 KEY = ' . $orderNo);
            // 这里处理更多的业务逻辑
            list($type, $order_no, $time) = explode(':', $orderNo);
            switch ($type) {
                case self::PAY:
                    if ($time == 5) {
                        $this->createOrderSendCode($order_no);
                    } elseif ($time == 30) {
                        $this->orderAutoCancel($order_no);
                    }
                    break;
                case self::NOTICE:
                    Log::error('NOTICE...');
                    break;
                default:
                    Log::error('default message');
            }
        });
    }

    /**
     * 生成订单60秒后,给用户发短信
     * @param $orderNo
     * @return bool
     */
    private function createOrderSendCode($orderNo)
    {
        $orderInfo = Order::where('order_no', '=', $orderNo)->find();
        if (!$orderInfo) {
            Log::error($orderNo . " 订单号不存在");
            return false;
        }

        $merchantInfo = Merchant::get($orderInfo->mch_id);
        if (!$merchantInfo) {
            Log::error($orderNo() . " 商户不存在");
            return false;
        }
        // 发送一份邮件
        $taskType = MultiTask::EMAIL;
        $data = [
          'email' => $merchantInfo->email,
          'title' => "订单号: " . $orderNo,
          'content' => "让您做一个电商平台，您如何设置一个在买家下订单后的发短信通知卖家发货 " . rand(11111, 999999)
        ];
        $res = multi_task_Queue($taskType, $data);
        if ($res !== false) {
            Log::error('用户发短信发送成功');
        } else {
            Log::error('用户发短信发送失败');
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
        //$orderInfo = Order::where('order_no', '=', $orderNo)->find();
        Log::error('自动取消订单号:' . $orderNo);
    }
}