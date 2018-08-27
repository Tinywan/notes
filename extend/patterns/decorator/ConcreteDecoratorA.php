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


class ConcreteDecoratorA extends Decorator
{
    function __construct(ComponentInterface $component)
    {
        parent::__construct($component);
    }

    public function operation()
    {
        parent::operation();    //  调用装饰类的操作
        $this->addedOperationA();   //  新增加的操作
    }

    public function addedOperationA() {
        echo 'A加点酱油;';
    }
}