<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/9/13 22:01
 * |  Mail: 756684177@qq.com
 * |  Desc: 描述信息
 * '------------------------------------------------------------------------------------------------------------------*/

namespace redis\lock;


class LockInit implements Lock
{
    public function lock($key, $val, $ttl)
    {
        return location_redis()->set($key, $val, $ttl);
    }

    public function unlock($key, $val)
    {
        // 自己加的锁，自己释放
        if (location_redis()->get($key)) {
            return location_redis()->del($key)>0;
        }
        return false;
    }
}