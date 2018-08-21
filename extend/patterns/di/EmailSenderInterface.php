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

/**
 * 为邮件服务定义抽象层
 * Interface EmailSenderInterface
 * @package patterns\di
 */
interface EmailSenderInterface
{
    public function send();
}