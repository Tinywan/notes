<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/9/13 21:59
 * |  Mail: 756684177@qq.com
 * |  Desc: 描述信息
 * '------------------------------------------------------------------------------------------------------------------*/

namespace redis\lock;

interface Lock
{
    /**
     * 枷锁
     * @param $key
     * @param $val
     * @param $ttl
     * @return mixed
     */
    public function lock($key, $val, $ttl);

    /**
     * 释放锁
     * @param $key
     * @param $val
     * @return mixed
     */
    public function unlock($key, $val);
}