<?php

/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/8/26 16:20
 * |  Mail: 756684177@qq.com
 * |  Desc: 装饰器类
 * '------------------------------------------------------------------------------------------------------------------*/

namespace patterns\decorator;

class Canvas
{
    public $data;
    /**
     * 所有添加过的装饰器
     * @var array
     */
    protected $decorators = array();

    // Decorator
    public function init($width = 20, $height = 10)
    {
        $data = array();
        for ($i = 0; $i < $height; $i++) {
            for ($j = 0; $j < $width; $j++) {
                $data[$i][$j] = '*';
            }
        }
        $this->data = $data;
    }

    /**
     * 动态添加装饰器对象
     * @param DrawDecorator $decorator
     */
    public function addDecorator(DrawDecorator $decorator)
    {
        $this->decorators[] = $decorator;
    }

    /**
     * 先进先出
     */
    public function beforeDraw()
    {
        // 循环去调用没有装饰器的方法
        foreach ($this->decorators as $decorator) {
            $decorator->beforeDraw();
        }
    }

    /**
     * 后进先出
     */
    public function afterDraw()
    {
        // 翻转
        $decorators = array_reverse($this->decorators);
        foreach ($decorators as $decorator) {
            $decorator->afterDraw();
        }
    }

    public function draw()
    {
        $this->beforeDraw();
        foreach ($this->data as $line) {
            foreach ($line as $char) {
                echo $char;
            }
            echo "<br />\n";
        }
        $this->afterDraw();
    }

    public function rect($a1, $a2, $b1, $b2)
    {
        foreach ($this->data as $k1 => $line) {
            if ($k1 < $a1 or $k1 > $a2) continue;
            foreach ($line as $k2 => $char) {
                if ($k2 < $b1 or $k2 > $b2) continue;
                $this->data[$k1][$k2] = '&nbsp;';
            }
        }
    }
}