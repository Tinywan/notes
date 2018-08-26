<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/8/26 15:21
 * |  Mail: 756684177@qq.com
 * |  Desc: 女性具体类
 * '------------------------------------------------------------------------------------------------------------------*/

namespace patterns\strategy;


class FemaleUserStrategy implements UserStrategy
{
    public function showAd()
    {
        echo "2014款女装广告";
    }

    public function showCategory()
    {
        echo '女装';
    }
}