<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/7/1 8:35
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 描述信息
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\pay\controller;


use app\common\controller\PayController;
use app\common\validate\Base;
use redis\BaseRedis;
use think\facade\Log;

class Order extends PayController
{
    /**
     * 支付同步通知消息
     */
    public function payReturnMessage()
    {

    }

    /**
     * 支付异步通知消息
     */
    public function payNoticeMessage()
    {
        $redis = location_redis();
    }

    /**
     * 消费者，取订单
     */
    public function consumerDelayMessage()
    {
        $redis = BaseRedis::location();
        while (true) {
            $items = $redis->zRange(self::ORDER_DELAY_KEY, 0, -1);
            if (empty($items)) {
                //Log::debug('no waiting tasks');
                echo "no waiting tasks\r\n";
                sleep(1);
                continue;
            }
            $score = $redis->zScore(self::ORDER_DELAY_KEY, $items[0]);
            list($msec, $sec) = explode(' ', microtime());
            $millisecond = (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
            if ($millisecond >= $score) {
                $member = $items[0];
                $memberData = json_decode($member, true);
                $num = $redis->zrem(self::ORDER_DELAY_KEY, $member);
                // 高并发条件下，多消费者会取到同一个订单号,对ZREM的返回值进行判断，只有大于0的时候，才消费数据
                if (!empty($num) && $num > 0) {
                    echo "Consumed OrderId is " . $memberData['order_no'] . "\r\n";
//                    Log::debug("Consumed OrderId is " . json_encode($memberData));
                }
            }
        }
    }

    /**
     * 生产者,生成5个订单放进去
     * 队列命名规则：业务:事件:具体 QUEUES:DELAY:ORDER
     * {
     * "order_no": "OID00000010",
     * "data": {
     * "id": 0,
     * "time": 1530579527175
     * },
     * "sign": "77IasdasadIasdadadadKL8t0"
     * }
     */
    private function productionDelayMessage()
    {
        $redis = BaseRedis::location();
        for ($i = 0; $i < 50000; $i++) {
            //延迟3秒
            list($msec, $sec) = explode(' ', microtime());
            $millisecond = (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
            $rand = rand(111111,999999);
            $data = [
              'order_no' => $rand."-S000000" . $i,
              'data' => [
                'id' => $i,
                'time' => $millisecond
              ],
              'sign' => '77IasdasadIasdadadadKL8t' . $i
            ];
            $redis->zAdd(self::ORDER_DELAY_KEY, $millisecond, json_encode($data));
            echo "create OrderNo ".$rand."-S0000001" . $i . "\r\n";
        }
    }

    /**
     * 测试
     */
    public function messageTest()
    {
        echo "Starting ...\r\n";
//        $this->productionDelayMessage();
        $this->consumerDelayMessage();
    }

    public function test()
    {
        $redis = BaseRedis::location();
        $items = $redis->zRange(self::ORDER_DELAY_KEY, 0, 1);
        $score = $redis->zScore(self::ORDER_DELAY_KEY, $items[0]);
        echo $score;
        var_dump($items[0]);
        $data = json_decode($items[0], true);
        $num = $redis->zrem(self::ORDER_DELAY_KEY, $items[0]);
        var_dump($data);
        halt($items);
    }

    public function test33()
    {
        $this->productionDelayMessage();
    }
}