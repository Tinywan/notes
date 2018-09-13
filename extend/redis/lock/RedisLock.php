<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/9/13 22:28
 * |  Mail: 756684177@qq.com
 * |  Desc: 使用Redis实现分布式锁
 * '------------------------------------------------------------------------------------------------------------------*/

namespace redis\lock;


class RedisLock
{
    /**
     * 获取锁
     * @param $lock_name 锁名
     * @param int $acquire_time 重复请求次数
     * @param int $lock_timeout 请求超时时间
     * @return bool|string
     */
    public static function acquire_lock($lock_name, $acquire_time = 3, $lock_timeout = 10)
    {
        $identifier = md5($_SERVER['REQUEST_TIME'] . mt_rand(1, 10000000));
        $lock_name = 'LOCK:' . $lock_name;
        $lock_timeout = intval(ceil($lock_timeout));
        $end_time = time() + $acquire_time;
        while (time() < $end_time) {
            $script = <<<luascript
                 local result = redis.call('setnx',KEYS[1],ARGV[1]);
                    if result == 1 then
                        redis.call('expire',KEYS[1],ARGV[2])
                        return 1
                    elseif redis.call('ttl',KEYS[1]) == -1 then
                       redis.call('expire',KEYS[1],ARGV[2])
                       return 0
                    end
                    return 0
luascript;
            $result = location_redis()->evaluate($script, array($lock_name, $identifier, $lock_timeout), 1);
            if ($result == '1') {
                return $identifier;
            }
            usleep(100000);
        }
        return false;
    }

    /**
     * 释放锁
     * @param $lock_name 锁名
     * @param $identifier 获取锁返回的标识
     * @return bool
     */
    public static function release_lock($lock_name, $identifier)
    {
        $lock_name = 'LOCK:' . $lock_name;
        while (true) {
            $script = <<<luascript
                local result = redis.call('get',KEYS[1]);
                if result == ARGV[1] then
                    if redis.call('del',KEYS[1]) == 1 then
                        return 1;
                    end
                end
                return 0
luascript;
            $result = location_redis()->evaluate($script, array($lock_name, $identifier), 1);
            if ($result == 1) {
                return true;
            }
            break;
        }
        //进程已经失去了锁
        return false;
    }
}