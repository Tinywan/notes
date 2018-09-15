<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/6/4 19:38
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 描述信息
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\common\command;

use redis\lock\RedisLock;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\facade\Log;

class Crontab extends Command
{
    // 配置指令
    public function configure()
    {
        $this->setName('crontab')
          ->addArgument('name', Argument::REQUIRED, "the name of the task that crontab needs to run")
          ->addOption('time', '-t', Option::VALUE_NONE, 'script runtime parameters')
          ->setDescription('this is thinkphp command crontab scheduled tasks');
    }

    // 执行指令
    public function execute(Input $input, Output $output)
    {
        $name = $input->getArgument('name');
        if ($name == "mysqldump") {
            $this->mysqlDump();
        } elseif ($name == 'order-query') {
            // 订单查询
            $this->orderQuery();
        } elseif ($name == 'redisLua1') {
            // redisLua1
            $this->redisLua1();
        } elseif ($name == 'redisLua2') {
            // redisLua2
            $this->redisLua2();
        }
    }

    // MySQL 自动备份功能
    private function mysqlDump()
    {
        $cmdStr = "/home/www/bin/mysql_auto_backup.sh backup >/dev/null 2>&1";
        exec("{$cmdStr}", $outputResult, $status);
        if ($status != 0) {
            Log::error('[' . get_current_date() . ']:' . ' system exec() run shell failed ,return code : ' . $status);
        } else {
            Log::debug('[' . get_current_date() . ']:' . ' mysqlDump is success ,return code : ' . $status);
        }
    }

    /**
     * 订单查询
     */
    private function orderQuery()
    {
        $order = [
          'out_trade_no' => '242381531814325',
          'bill_type' => 'trade'
        ];
        $pay = \Yansongda\Pay\Pay::alipay(config('pay.alipay'));
        $result = $pay->find($order);
        Log::error('订单查询' . \GuzzleHttp\json_encode($result));
        echo 11111111111;
    }

    private function redisLua1()
    {
        for ($i = 0; $i < 100000; $i++) {
            $order = rand(1, 3);
            $order_no = 'order_' . $order;
            Log::debug('[异步脚本] 开始...' . $order_no);
            $orderLock = RedisLock::acquireLock($order_no);
            if (!$orderLock) {
                Log::debug('[异步脚本] 获取锁失败 ' . $order_no);
                continue;
            } else {
                Log::debug('[异步脚本] 获取锁成功 ' . $order_no);
            }
            // 处理业务逻辑
            Log::debug('[异步脚本] 处理业务 ' . $order_no);
            sleep(2);
            // 释放锁
            $orderUnLock = RedisLock::releaseLock($order_no, $orderLock); // 7f62708bb826c034850783efdba127b3
            if (!$orderUnLock) {
                Log::debug('[异步脚本] 释放锁失败 ' . $order_no);
            } else {
                Log::debug('[异步脚本] 释放锁成功 ' . $order_no . PHP_EOL);
            }
        }
    }

    private function redisLua2()
    {
        for ($i = 0; $i < 100000; $i++) {
            $order = rand(1, 3);
            $order_no = 'order_' . $order;
            Log::debug('[命令行] 开始...' . $order_no);
            $orderLock = RedisLock::acquireLock($order_no);
            if (!$orderLock) {
                Log::debug('[命令行] 获取锁失败 ' . $order_no);
                continue;
            } else {
                Log::debug('[命令行] 获取锁成功 ' . $order_no);
            }
            // 处理业务逻辑
            Log::debug('[命令行] 处理业务 ' . $order_no);
            sleep(2);
            // 释放锁
            $orderUnLock = RedisLock::releaseLock($order_no, $orderLock);
            if (!$orderUnLock) {
                Log::debug('[命令行] 释放锁失败 ' . $order_no);
            } else {
                Log::debug('[命令行] 释放锁成功 ' . $order_no . PHP_EOL);
            }
        }
    }
}