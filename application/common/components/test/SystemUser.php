<?php

/** .-----------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |-------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/6/2 13:41
 * |  Mail: Overcome.wan@Gmail.com
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\common\components\test;


class SystemUser
{
    const STSTUS = 0;

    /**
     * 被任务队列调用
     * @return string
     */
    public function create()
    {
        return "create" . __METHOD__;
    }
}