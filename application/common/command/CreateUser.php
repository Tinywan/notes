<?php
/** .-----------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |-------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/6/2 13:41
 * |  Mail: Overcome.wan@Gmail.com
 * '------------------------------------------------------------------------------------------------------------------*/
namespace app\common\command;

use app\common\components\test\SystemUser;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;

class CreateUser extends Command
{
    protected function configure()
    {
        $this->setName('hello')
            ->addArgument('name', Argument::REQUIRED, "your name")
            ->addOption('city', null, Option::VALUE_REQUIRED, 'city name')
            ->setDescription('Say Hello');
    }

    protected function execute(Input $input, Output $output)
    {
        $name = trim($input->getArgument('name'));
        $name = $name ?: 'thinkphp';

        if ($input->hasOption('city')) {
            $city = PHP_EOL . 'From ' . $input->getOption('city');
        } else {
            $city = '';
        }

        if ($name == 'systemUser'){
            $res = PHP_EOL.$this->systemUser();
        }
        $res = $res??'Default User';

        $output->writeln("Hello," . $name . '!' . $city."\r\n".$res);
    }

    /**
     * 创建系统用户
     * @return string
     */
    private function systemUser()
    {
        $model = new SystemUser();
        return $model->create();
    }

}