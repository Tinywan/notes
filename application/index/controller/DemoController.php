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
use redis\BaseRedis;
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

    public function hooks()
    {
        echo __FUNCTION__;
        echo __FUNCTION__;
        echo __FUNCTION__;
        echo __FUNCTION__;
    }

}
