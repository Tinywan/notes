<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件

/**
 * QQ服务器发送邮件
 * @param  array $address 需要发送的邮箱地址 发送给多个地址需要写成数组形式
 * @param  string $subject 标题
 * @param  string $content 内容
 * @return array  放回状态吗和提示信息
 */
function send_email_qq($address, $subject, $content)
{
    $email_smtp_host = \think\facade\Config::get('email.qq.smtp_host');
    $email_username = \think\facade\Config::get('email.qq.username');
    $email_password = \think\facade\Config::get('email.qq.password');
    $email_from_name = \think\facade\Config::get('email.qq.from_name');
    $email_host = \think\facade\Config::get('email.qq.domain');
    if (empty($email_smtp_host) || empty($email_username) || empty($email_password) || empty($email_from_name)) {
        return ["errorCode" => 1, "msg" => '邮箱请求参数不全，请检测邮箱的合法性'];
    }
    $phpmailer = new PHPMailer\PHPMailer\PHPMailer();
    $phpmailer->SMTPDebug = 0;
    $phpmailer->IsSMTP();
    $phpmailer->SMTPAuth = true;
    $phpmailer->SMTPSecure = 'ssl';
    $phpmailer->Host = $email_smtp_host;
    $phpmailer->Port = 465;
    $phpmailer->Hostname = $email_host;
    $phpmailer->CharSet = 'UTF-8';
    $phpmailer->FromName = $email_username;
    $phpmailer->Username = $email_username;
    $phpmailer->Password = $email_password;
    $phpmailer->From = $email_username;
    $phpmailer->IsHTML(true);
    if (is_array($address)) {
        foreach ($address as $addressv) {
            if (false === filter_var($address, FILTER_VALIDATE_EMAIL)) {
                return ["errorCode" => 1, "msg" => '邮箱格式错误'];
            }
            $phpmailer->AddAddress($addressv, $address.'的['.$subject.']');
        }
    } else {
        if (false === filter_var($address, FILTER_VALIDATE_EMAIL)) {
            return ["errorCode" => 1, "msg" => '邮箱格式错误'];
        }
        $phpmailer->AddAddress($address, $address.'的['.$subject.']');
    }
    $phpmailer->Subject = $subject;
    $phpmailer->Body = $content;
    if (!$phpmailer->Send()) {
        return ["errorCode" => 1, "msg" => $phpmailer->ErrorInfo];
    }
    return ["errorCode" => 0];
}

/**
 * 多任务队列
 * @param $taskType
 * @param $data
 * @return string
 */
function multi_task_Queue($taskType, $data)
{
    if (empty($taskType) || !is_numeric($taskType) || empty($data))
    {
        return ["errorCode" => 10002, "msg" => '请求参数错误'];
    }
    switch ($taskType) {
        case \app\common\queue\MultiTask::EMAIL: // 发送邮件
            $className = \app\common\queue\MultiTask::class . "@sendEmail";
            $queueName = "multiTaskQueue";
            break;
        case \app\common\queue\MultiTask::SMS:
            $className = \app\common\queue\MultiTask::class . "@sendSms";
            $queueName = "multiTaskQueue";
            break;
        case \app\common\queue\MultiTask::MSG:
            $className = \app\common\queue\MultiTask::class . "@sendMsg";
            $queueName = "multiTaskQueue";
            break;
    }
    $isPushed = \think\Queue::push($className, $data, $queueName);
    if ($isPushed) return true;
    return false;
}
