<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/8/26 15:22
 * |  Mail: 756684177@qq.com
 * |  Desc: 男性具体类
 * '------------------------------------------------------------------------------------------------------------------*/

namespace patterns\strategy;


class MaleUserStrategy implements UserStrategy
{
    public function showAd()
    {
        echo "IPhone 8 Plus";
    }

    public function showCategory()
    {
        echo "电子商品";
    }
}