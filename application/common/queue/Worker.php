<?php
/** .-----------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |-------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/6/2 13:41
 * |  Mail: Overcome.wan@Gmail.com
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\common\queue;

use think\Db;
use think\queue\Job;

class Worker
{
    /**
     * 消息队列默认调用的方法
     * @param Job $job 当前的任务对象
     * @param $data 任务所需的业务数据
     */
    public function fire(Job $job, $data)
    {
        // 如有必要,可以根据业务需求和数据库中的最新数据,判断该任务是否仍有必要执行.
        $status = $this->checkDbStatus($data);
        if (!$status) {
            $job->delete();
            return;
        }

        $isJobDone = $this->insertDb($data);

        if ($isJobDone) {
            //成功删除任务
            $job->delete();
        } else {
            //任务轮询4次后删除
            $attempts = $job->attempts(); // 通过这个方法可以检查这个任务已经重试了几次了
            if ($attempts > 3) {
                // 第1种处理方式：重新发布任务,该任务延迟10秒后再执行
                //$job->release(10);
                // 第2种处理方式：原任务的基础上1分钟执行一次并增加尝试次数
                //$job->failed();
                // 第3种处理方式：删除任务
                $job->delete();
            }
        }
    }

    /**
     * 任务达到最大重试次数
     * @param $data 业务数据
     */
    public function failed($data)
    {
        // ...任务达到最大重试次数后，失败了
    }

    /**
     * @param $data 业务数据
     * @return bool 任务执行的结果
     */
    private function checkDbStatus($data)
    {
        return true;
    }

    /**
     * 消息队列插入数据库
     * @param $data 任务所需的业务数据
     * @return bool 任务执行的结果
     */
    private function insertDb($data)
    {
        $result = Db::name('order_queue')->insert([
            'utime' => time(),
            'email' => $data['email'],
            'username' => $data['username']
        ]);
        if ($result) return true;
        return false;
    }
}