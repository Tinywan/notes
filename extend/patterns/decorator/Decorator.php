<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/8/28 0:05
 * |  Mail: 756684177@qq.com
 * |  Desc: 描述信息
 * '------------------------------------------------------------------------------------------------------------------*/

namespace patterns\decorator;

// 装饰角色
class Decorator implements ComponentInterface
{
    // 组装器
    protected  $_component;

    function __construct(ComponentInterface $component)
    {
        $this->_component = $component;
    }

    public function operation()
    {
        $this->_component->operation();
    }
}