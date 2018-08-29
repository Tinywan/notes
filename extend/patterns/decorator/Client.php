<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/8/28 9:41
 * |  Mail: 756684177@qq.com
 * |  Desc: 描述信息
 * '------------------------------------------------------------------------------------------------------------------*/

namespace patterns\decorator;


class Client
{
    public function client(){
        $component = new ConcreteComponent(); // 具体构件角色,真实对象
        $component->operator();

        // 前置增强
        $afterComponent = new AfterAdviceDecorator($component);
        $afterComponent->operator();

        // + 后置增强
        $beforeComponent = new BeforeAdviceDecorator($component);
        $beforeComponent->operator();
    }
}