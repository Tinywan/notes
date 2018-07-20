<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/7/20 13:27
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 描述信息
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\common\presenters;


use Carbon\Carbon;

class DateFormatPresenter_uk implements DateFormatPresenterInterface
{
    public function showDateFormat(Carbon $date): string
    {
        // TODO: Implement showDateFormat() method.
        return $date->format('M d, Y');
    }
}