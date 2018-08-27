<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/8/27 23:47
 * |  Mail: 756684177@qq.com
 * |  Desc: 描述信息
 * '------------------------------------------------------------------------------------------------------------------*/

namespace patterns\factory;


class SystemFactory implements SystemFactoryInterface
{
    // 实现工厂方法
    public function createSystem($type)
    {
        switch ($type){
            case 'Win':
                return new WinSystem();
            case 'Mac':
                return new MacSystem();
            case 'Linux':
                return new LinuxSystem();
        }
    }
}

// 创建我的系统工厂
$factory = new SystemFactory();
//用我的系统工厂分别创建不同系统对象
print_r($factory->createSystem('Linux')); // patterns\factory\LinuxSystem Object ( )
echo "<br>";
print_r($factory->createSystem('Win')); // patterns\factory\WinSystem Object ( )
echo "<br>";
print_r($factory->createSystem('Mac')); // patterns\factory\MacSystem Object ( )