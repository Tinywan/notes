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


use think\facade\Log;

class SystemUser
{
    const STSTUS = 0;

    /**
     * 被任务队列调用
     * @return string
     */
    public function create()
    {
        Log::error("create" . __METHOD__);
        return "create" . __METHOD__;
    }
}