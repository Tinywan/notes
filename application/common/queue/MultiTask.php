<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/6/2 21:52
 * |  Mail: Overcome.wan@Gmail.com
 * |  Fun: 多任务队列
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\common\queue;

use think\Db;
use think\Exception;
use think\facade\Log;
use think\queue\Job;

class MultiTask
{
    const EMAIL = 1; // 邮件
    const SMS = 2; // 短信
    const MSG = 3; // 消息

    /**
     * 发送短信
     * @param Job $job
     * @param $data 数据格式
       $data = [
        'mobile' => 13669361192,
        'code' => rand(000000, 999999)
       ];
     */
    public function sendSms(Job $job, $data)
    {
        $this->_sendSms($data);
        $job->delete();
    }

    /**
     * 队列发送邮件
     * @param Job $job
     * @param $data
     * $data = [
        'email' => '756684177@qq.com',
        'title' => "邮件标题",
        'content' => "邮件内容"
       ];
     */
    public function sendEmail(Job $job, $data)
    {
        $isDone = $this->_sendEmail($data);
        if ($isDone) {
            $job->delete();
        } else {
            $attempts = $job->attempts();
            switch ($attempts) {
                case 1: // 重新发布任务,该任务延迟2秒后再执行
                    $job->release(2);
                    break;
                case 2:
                    $job->release(10);
                    break;
                default:
                    $job->delete();
            }
        }
    }

    /**
     * 发送消息
     * @param Job $job
     * @param $data
     */
    public function sendMsg(Job $job, $data)
    {
        $this->_sendSms($data);
        $job->delete();
    }

    /**
     * 发送短信
     * @param $data
     * @return bool
     */
    private function _sendSms($data)
    {
        try {
            $result = Db::name('resty_wx_user1')->update($data);
            if ($result) return true;
            return false;
        } catch (Exception $e) {
            Log::error(__CLASS__ . __METHOD__ . json_encode($e->getMessage()));
        }
        return false;
    }

    /**
     * 发送Email
     * @param $data
     * @return bool
     */
    private function _sendEmail($data)
    {
        $res = send_email_qq($data['email'], $data['title'], $data['content']);
        if (isset($res['errorCode']) && ($res['errorCode'] == 0)) return true;
        return false;
    }

    /**
     *
     * @param $data
     * @return bool
     */
    private function _downloadExcel($data)
    {
        $res = send_email_qq($data['email'], $data['title'], $data['content']);
        if (isset($res['errorCode']) && ($res['errorCode'] == 0)) return true;
        return false;
    }

    /**
     *
     * @param $data
     * @return bool
     */
    private function _downloadCsv($data)
    {
        $res = send_email_qq($data['email'], $data['title'], $data['content']);
        if (isset($res['errorCode']) && ($res['errorCode'] == 0)) return true;
        return false;
    }

    /**
     * ================================================Mysql 数据库=============================================
     * @param Job $job
     * @param $data
     */
    public function add(Job $job, $data)
    {
        $isDone = $this->_add($data);
        if ($isDone) {
            $job->delete();
            print(__METHOD__ . " Job MultiTask " . "\n");
        } else {
            $attempts = $job->attempts();
            switch ($attempts) {
                case 1: // 重新发布任务,该任务延迟2秒后再执行
                    $job->release(2);
                    break;
                case 2:
                    $job->release(10);
                    break;
                default:
                    $job->delete();
            }
        }
    }

    /**
     * 更新
     * @param Job $job
     * @param $data
     */
    public function update(Job $job, $data)
    {
        $isDone = $this->_update($data);
        if ($isDone) {
            $job->delete();
        } else {
            if ($job->attempts() > 2) {
                $job->release();
            }
        }
    }

    /**
     * 删除
     * @param Job $job
     * @param $data
     */
    public function delete(Job $job, $data)
    {
        $this->_delete($data);
        $job->delete();
    }


    /**
     * _add 操作
     * @param $data
     * @return bool
     */
    private function _add($data)
    {
        try {
            $result = Db::name('resty_wx_user1')->insert($data);
            if ($result) return true;
            return false;
        } catch (Exception $e) {
            Log::error(__CLASS__ . __METHOD__ . json_encode($e->getMessage()));
        }
        return false;
    }

    /**
     * _update
     * @param $data
     * @return bool
     */
    private function _update($data)
    {
        try {
            $result = Db::name('resty_wx_user1')->update($data);
            if ($result) return true;
            return false;
        } catch (Exception $e) {
            Log::error(__CLASS__ . __METHOD__ . json_encode($e->getMessage()));
        }
        return false;
    }

}