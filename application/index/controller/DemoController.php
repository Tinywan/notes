<?php
/** .-----------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |-------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/6/2 13:41
 * |  Mail: Overcome.wan@Gmail.com
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\index\controller;

use app\common\model\Order;
use app\common\presenter\DateFormatPresenter_tw;
use app\common\presenter\DateFormatPresenter_uk;
use app\common\queue\MultiTask;
use app\common\queue\Worker;
use Medz\IdentityCard\China\Identity;
use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;
use Ramsey\Uuid\Uuid;
use redis\BaseRedis;
use think\Db;
use think\facade\Config;
use think\facade\Env;
use think\facade\Log;
use think\helper\Time;
use think\Queue;
use Yansongda\Pay\Pay;

class DemoController
{
    /**
     * 测试多任务队列
     * @return string
     */
    public function testMultiTaskQueue()
    {
        $taskType = MultiTask::EMAIL;
        $data = [
          'email' => '756684177@qq.com',
          'title' => "把保存在内存中的日志信息",
          'content' => "把保存在内存中的日志信息（用指定的记录方式）写入，并清空内存中的日志" . rand(11111, 999999)
        ];
        halt(send_email_qq($data['email'], $data['title'], $data['content']));
        //$res = send_email_qq($data['email'], $data['title'], $data['content']);
        $res = multi_task_Queue($taskType, $data);
        if ($res !== false) {
            return "Job is Pushed to the MQ Success";
        } else {
            return 'Pushed to the MQ is Error';
        }
    }

    /**
     * 订单过期通知
     */
    public function orderExpireNotice()
    {
        $redis = BaseRedis::location();
        $res = $redis->setex('S120012018033016125053041', 3, time());
        halt($res);
    }

    public function sendEmail()
    {
        $res = send_email_qq('756684177@qq.com', 'test', 'content');
        var_dump($res);
    }

    public function fastCgi()
    {
        echo "program start...\r\n";
        $file = Env::get('ROOT_PATH') . '/logs/aliPay.log';
        file_put_contents($file, 'start-time:' . get_current_date() . "\r\n", FILE_APPEND);
        fastcgi_finish_request();

        sleep(1);
        echo 'debug...' . "\r\n";
        file_put_contents($file, 'start-proceed:' . date('Y-m-d H:i:s') . "\r\n", FILE_APPEND);

        sleep(10);
        file_put_contents($file, 'end-time:' . date('Y-m-d H:i:s') . "\r\n", FILE_APPEND);
    }

    /**
     * http://notes.frp.tinywan.top/index/demo/aliPay
     *  网关支付demo
     * @auther Tinywan 756684177@qq.com
     * @DateTime 2018/8/12 15:10
     * @return mixed
     */
    public function aliPay()
    {
        $order_no = 'S' . date('ymdHis', time()) . rand(1000, 9999);
        $insertData = [
          'mch_id' => '2025801203065130',
          'order_no' => $order_no,
          'total_amount' => rand(11, 99),
          'goods' => '商品测试00' . rand(1111, 9999),
        ];
        $res = Order::create($insertData);
        if ($res) {
            $payOrder = [
              'out_trade_no' => $insertData['order_no'],
              'total_amount' => $insertData['total_amount'],
              'subject' => $insertData['goods'],
            ];
            $alipay = Pay::alipay(config('pay.alipay'))->web($payOrder);
            return $alipay->send();
        }
        halt($res);
    }

    /**
     * 渠道支付
     * @auther Tinywan 756684177@qq.com
     * @DateTime 2018/8/12 15:23
     * @return mixed
     */
    public function channelPay()
    {
        $order_no = 'S' . date('ymdHis', time()) . rand(1000, 9999);
        $insertData = [
          'mch_id' => '2025801203065130',
          'order_no' => $order_no,
          'total_amount' => rand(11, 99),
          'goods' => '商品测试00' . rand(1111, 9999),
        ];
        $res = Order::create($insertData);
        if ($res) {
            $payOrder = [
              'out_trade_no' => $insertData['order_no'],
              'total_amount' => $insertData['total_amount'],
              'subject' => $insertData['goods'],
            ];
            $alipay = Pay::alipay(config('pay.alipay'))->web($payOrder);
            return $alipay->send();
        }
        halt($res);
    }


    public function presenterDate()
    {
        $locale = 'uk';
        if ($locale === 'uk') {
            $presenter = new DateFormatPresenter_uk();
        } elseif ($locale === 'tw') {
            $presenter = new DateFormatPresenter_tw();
        } else {
            $presenter = new DateFormatPresenter_tw();
        }
        return view('users.index', compact('users'));
    }

    public function Uuid()
    {
        try {

            // Generate a version 1 (time-based) UUID object
            //$uuid1 = Uuid::uuid1();
            //echo $uuid1->toString() . "\n"; // i.e. e4eaaaf2-d142-11e1-b3e4-080027620cdd

            // Generate a version 3 (name-based and hashed with MD5) UUID object
            $uuid3 = Uuid::uuid3(Uuid::NAMESPACE_DNS, 'php.net');
            echo $uuid3->toString() . "\r\n"; // i.e. 11a38b9a-b3da-360f-9353-a5a725514269

            // Generate a version 4 (random) UUID object
            $uuid4 = Uuid::uuid4();
            echo $uuid4->toString() . "\r\n"; // i.e. 25769c6c-d34d-4bfe-ba98-e0ee856f3e7a

            // Generate a version 5 (name-based and hashed with SHA1) UUID object
            $uuid5 = Uuid::uuid5(Uuid::NAMESPACE_DNS, 'php.net');
            echo $uuid5->toString() . "\r\n"; // i.e. c4a760a8-dbcf-5254-a0d9-6a4474bd1b62

        } catch (UnsatisfiedDependencyException $e) {
            // Some dependency was not met. Either the method cannot be called on a
            // 32-bit system, or it can, but it relies on Moontoast\Math to be present.
            echo 'Caught exception: ' . $e->getMessage() . "\r\n";

        }
    }

    public function mongo()
    {
        // 查询操作
        $user = Db::table('test')
            ->where('_id','589461c0fc122812b4007411')
            ->find();
        halt($user);
    }

    public function testFun(int ...$ints)
    {
        return array_sum($ints);
    }

    public function arraysSum(array ...$arrays): array
    {
        return array_map(function (array $arr): int {
            return array_sum($arr);
        },$arrays);
    }

    public function php7()
    {
        var_dump($this->testFun(2,33,4.5));
        var_dump($this->arraysSum([2,33,4.5]));
    }

}
