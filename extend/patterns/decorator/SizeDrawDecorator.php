<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/8/26 16:37
 * |  Mail: 756684177@qq.com
 * |  Desc: 文字大小装饰器
 * '------------------------------------------------------------------------------------------------------------------*/

namespace patterns\decorator;


class SizeDrawDecorator implements DrawDecorator
{
    protected $size;

    public function __construct($size = '14px')
    {
        $this->size = $size;
    }

    public function beforeDraw()
    {
        echo "<div style='font-size: {$this->size};'>";
    }

    public function afterDraw()
    {
        echo "</div>";
    }
}