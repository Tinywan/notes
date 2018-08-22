<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/8/21 15:51
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 依赖注入
 * '------------------------------------------------------------------------------------------------------------------*/

namespace patterns\di;


class TencentSender implements EmailSenderInterface
{
    // 实现发送邮件的类方法
    public function send()
    {
        // TODO: Implement send() method.
        echo __CLASS__;
    }
}