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


// 抽象构件角色, 真实对象和装饰对象的共有接口. 这样,客户端就能以调用真实对象的相同方式同装饰对象交互.
interface Component
{
    public function operator();
}