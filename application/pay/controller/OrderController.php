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


use app\common\controller\BasePayController;
use redis\BaseRedis;

class OrderController extends BasePayController
{
    /**
     * 生产者,生成5个订单放进去
     */
    public function productionDelayMessage()
    {
        $redis = BaseRedis::location();
        for ($i = 0; $i < 20; $i++) {
            //延迟3秒
            list($msec, $sec) = explode(' ', microtime());
            $millisecond = (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
            $redis->zAdd('OrderId', $millisecond, "OID0000001" . $i);
            echo "create OrderNo OID0000001" . $i . "\r\n";
        }
    }

    /**
     * 消费者，取订单
     */
    public function consumerDelayMessage()
    {
        $redis = BaseRedis::location();
        while (true) {
            $items = $redis->zRange("OrderId", 0, -1);
            if (empty($items)) {
                echo 'no waiting tasks' . "\r\n";
                sleep(1);
                continue;
            }
            $score = $redis->zScore('OrderId', $items[0]);
            list($msec, $sec) = explode(' ', microtime());
            $millisecond = (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
            if ($millisecond >= $score) {
                $orderId = $items[0];
                $num = $redis->zrem("OrderId", $orderId);
                // 高并发条件下，多消费者会取到同一个订单号,对ZREM的返回值进行判断，只有大于0的时候，才消费数据
                if (!empty($num) && $num > 0) {
                    echo "Consumed OrderId is " . $orderId . "\r\n";
                }
            }
        }
    }

    /**
     * 测试
     */
    public function messageTest()
    {
        echo "Starting ...\r\n";
        list($type,$orderNo,$time) = explode(':','PAY:S120012018033017194343904:5');
        echo $type." ...\r\n";
        echo $orderNo." ...\r\n";
        echo $time." ...\r\n";
        $this->productionDelayMessage();
        $this->consumerDelayMessage();
    }
}