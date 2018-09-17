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
     * @param string $lock_name 锁名
     * @param int $acquire_time 重复请求次数
     * @param int $lock_timeout 请求超时时间
     * @return bool|string
     */
    public static function acquireLock($lock_name, $acquire_time = 3, $lock_timeout = 20)
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
            usleep(100000); //  函数延迟代码执行若干微秒
        }
        return false;
    }

    /**
     * 释放锁
     * @param string $lock_name 锁名
     * @param string $identifier 获取锁返回的标识
     * @return bool
     */
    public static function releaseLock($lock_name, $identifier)
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

    public static function test($lock_name, $identifier)
    {
        $lock_name = 'LOCK:' . $lock_name;
        echo $lock_name;
        echo $identifier;
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
        return location_redis()->evaluate($script, array($lock_name, $identifier, 120), 1);
    }

    /**
     * 防重复提交策略 https://www.cnblogs.com/tinywan/p/9663432.html
     * @param string $lock_name 锁名
     * @param bool $delete 是否删除
     * @return bool
     */
    public static function preventRepeatedSubmit($lock_name = 'S120012018040414374458006', $delete = false)
    {
        $redis = location_redis();
        $key = 'PREVENT_REPEATED:' . $lock_name;
        if ($delete) {
            if ($redis->del($key) == 1) {
                return true;
            } else {
                return false;
            }
        } else {
            if ($redis->incr($key) > 1) {
                // 不能获得锁,说明有操作在进行
                return false;
            }
            // 获得锁,可以操作
            return true;
        }
    }
}