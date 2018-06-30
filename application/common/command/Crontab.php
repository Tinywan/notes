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
        }
    }

    // MySQL 自动备份功能
    private function mysqlDump()
    {
        $cmdStr = "/home/www/bin/mysql_auto_backup.sh backup >/dev/null 2>&1";
        exec("{$cmdStr}", $outputResult, $status);
        if ($status != 0) {
            Log::error('[' . get_current_date() . ']:' . ' system exec() run shell failed ,return code : ' . $status);
        }else{
            Log::debug('[' . get_current_date() . ']:' . ' mysqlDump is success ,return code : ' . $status);
        }
    }
}