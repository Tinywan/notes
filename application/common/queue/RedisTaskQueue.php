<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/11/22 15:31
 * |  Mail: 756684177@qq.com
 * |  Desc: 描述信息
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\common\queue;


use think\facade\Log;
use think\queue\Job;

class RedisTaskQueue
{
    const MERCHANT_NOTIFY = 1; // 商户通知
    const TRANSFER_NOTIFY = 2; // 转账通知
    const MSG_NOTIFY = 3; // 消息

    /**
     * 商户通知
     * @param Job $job
     * @param $data
     * @return bool
     */
    public function merchantNotify(Job $job, $data)
    {
        try{
            $object = new RedisTaskQueue();
            $object->sendNotify($data['order_no'],2);
        }catch (\Exception $e){
            Log::error('队列执行失败'.$e->getMessage().'|'.$e->getTraceAsString());
            return false;
        }
        $job->delete();
        return true;
    }

    /**
     * 转账通知
     * @param Job $job
     * @param $data
     * @return bool
     */
    public function transferNotify(Job $job, $data)
    {
        try{
            Log::info('转账手动通知1 '.json_encode($data));
            Log::info('转账手动通知2 '.time());
        }catch (\Exception $e){
            Log::error('队列执行失败'.$e->getMessage().'|'.$e->getTraceAsString());
            return false;
        }
        $job->delete();
        return true;
    }
}