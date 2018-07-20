<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/7/20 13:08
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 描述信息
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\common\services;


use PHPMailer\PHPMailer\PHPMailer;

class EmailService
{
    /** @var PHPMailer */
    private $mail;

    // 將相依的PHPMailer 注入到 EmailService
    public function __construct(PHPMailer $mail)
    {
        $this->mail = $mail;
    }

    /**
     * 发送Email
     * @param array $request
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function send(array $request)
    {
        $this->mail->send('email.index', $request, function (Message $message) {
            $message->sender(env('MAIL_USERNAME'));
            $message->subject(env('MAIL_SUBJECT'));
            $message->to(env('MAIL_TO_ADDR'));
        });
    }

}