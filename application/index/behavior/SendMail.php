<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/10/30 15:23
 * |  Mail: 756684177@qq.com
 * |  Desc: 很简单的发送邮件通知的行为类
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\index\behavior;


use think\facade\Log;

class SendMail
{
    public function run($params)
    {
        // 发送邮件通知
        list($type, $message) = $params;
        if ('error' == $type) {
            Log::debug('发送邮件通知: '.json_encode($params));
            send_email_qq('756684177@qq.com','系统日志通知', implode(' ', $message));
        }
    }
}