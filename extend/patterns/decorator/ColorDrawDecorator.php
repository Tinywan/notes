<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/8/26 16:35
 * |  Mail: 756684177@qq.com
 * |  Desc: 颜色装饰器
 * '------------------------------------------------------------------------------------------------------------------*/

namespace patterns\decorator;


class ColorDrawDecorator implements DrawDecorator
{
    protected $color;

    public function __construct($color = 'red')
    {
        $this->color = $color;
    }

    public function beforeDraw()
    {
        echo "<div style='color: {$this->color};'>";
    }

    public function afterDraw()
    {
        echo "</div>";
    }
}