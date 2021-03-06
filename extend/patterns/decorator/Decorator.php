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

/**
 * 装饰抽象类, 实现了Component, 并持有一个Component引用, 接受所有客户端请求,并将请求转发给真实对象,
 *  这样，就能在真实对象调用的前后增强新功能. 但对于Component来说, 是无需知道Decorator存在的.
 * Class Decorator
 * @package patterns\decorator
 */
abstract class Decorator implements Component
{
    // 组装器
    protected  $_component;

    function __construct(Component $component)
    {
        $this->_component = $component;
    }
}