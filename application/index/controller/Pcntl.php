<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/7/4 8:58
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 描述信息
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\index\controller;


class Pcntl
{
    public function demo001(){
        $pid = pcntl_fork();
        if($pid === -1){
            die('could not fork');
        }elseif ($pid){
            //父进程会得到子进程号，所以这里是父进程执行的逻辑
            echo $pid;
            pcntl_wait($status);
        }else{
            //子进程得到的$pid为0, 所以这里是子进程执行的逻辑。
            echo $pid;
        }
    }
}