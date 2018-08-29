<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/8/28 9:39
 * |  Mail: 756684177@qq.com
 * |  Desc: 描述信息
 * '------------------------------------------------------------------------------------------------------------------*/

namespace patterns\decorator;


class AfterAdviceDecorator extends Decorator
{
    public function __construct(Component $component)
    {
        parent::__construct($component); // java super(component);
    }

    public function operator()
    {
        $this->_component->operator();
        echo '后置增强 ->';
    }
}