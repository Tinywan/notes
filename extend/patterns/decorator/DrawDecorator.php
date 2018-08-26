<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/8/26 16:27
 * |  Mail: 756684177@qq.com
 * |  Desc: 装饰器接口
 * '------------------------------------------------------------------------------------------------------------------*/

namespace patterns\decorator;


interface DrawDecorator
{
    public function beforeDraw();

    public function afterDraw();
}