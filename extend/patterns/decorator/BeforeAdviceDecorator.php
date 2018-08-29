<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/8/28 0:07
 * |  Mail: 756684177@qq.com
 * |  Desc: 描述信息
 * '------------------------------------------------------------------------------------------------------------------*/

namespace patterns\decorator;


class BeforeAdviceDecorator extends Decorator
{
    public function __construct(Component $component)
    {
        parent::__construct($component);
    }

    public function operator()
    {
        echo '-> 前置增强';
        $this->_component->operator();
    }
}