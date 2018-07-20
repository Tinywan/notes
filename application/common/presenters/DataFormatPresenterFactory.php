<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/7/20 13:34
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 描述信息
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\common\presenters;


use think\facade\App;

class DataFormatPresenterFactory
{
    /**
     * @param string $locale
     */
    public static function bind(string $locale)
    {
        App::invokeClass(DateFormatPresenterInterface::class,
            'MyBlog\Presenters\DateFormatPresenter_' . $locale);
    }
}