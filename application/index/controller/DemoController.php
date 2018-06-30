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

use app\common\queue\MultiTask;
use app\common\queue\Worker;
use redis\BaseRedis;
use think\facade\Config;
use think\facade\Log;
use think\Queue;

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
        halt(send_email_qq($data['email'],$data['title'],$data['content']));
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
        $res = $redis->setex('S120012018033016125053041',3,time());
        halt($res);
    }

    public function sendEmail()
    {
        $res = send_email_qq('756684177@qq.com', 'test', 'content');
        var_dump($res);
    }

}
