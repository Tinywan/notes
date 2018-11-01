<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/7/30 9:50
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 描述信息
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\index\controller;


class PcntlDemo
{
    // 信号处理函数
    public function signal_handle($signal)
    {
        switch ($signal){
            case SIGTERM:
                // 处理 SIGTERM 信号 - 进程终止
                exit;
                break;
            case SIGHUP:
                // 处理 SIGHUP 信号 - 终止控制终端或进程
                break;
            case SIGUSR1:
                // 用户信号
                echo "Caught SIGUSR1...\n";
                break;
            default:
                // 处理所有其他信号
        }
    }

    public function test_signal_handle()
    {
        echo "Installing signal handler...\n";

        // 安装信号处理器
        pcntl_signal(SIGTERM, function ($signal){
            switch ($signal){
                case SIGTERM:
                    // 处理 SIGTERM 信号 - 进程终止
                    echo"处理 SIGTERM 信号 - 进程终止...\n";
                    exit;
                    break;
                case SIGHUP:
                    // 处理 SIGHUP 信号 - 终止控制终端或进程
                    echo"处理 SIGHUP 信号 - 终止控制终端或进程...\n";
                    break;
                case SIGUSR1:
                    // 用户信号
                    echo "Caught SIGUSR1...\n";
                    break;
                default:
                    // 处理所有其他信号
            }
        });

        declare(ticks = 1);

        pcntl_signal(SIGUSR1, function ($signal) {
            echo 'HANDLE SIGNAL ' . $signal . PHP_EOL;
        });
        // or use an object, available as of PHP 4.3.0
        // pcntl_signal(SIGUSR1, array($obj, "do_something"));

        echo"Generating signal SIGUSR1 to self...\n";

        // send SIGUSR1 to current process id
        // posix_* functions require the posix extension
       // posix_kill(posix_getpid(), SIGUSR1);

        echo "Done\n";
    }


}