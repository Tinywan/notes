<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/7/20 13:23
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 描述信息
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\common\presenters;


use Carbon\Carbon;

interface  DateFormatPresenterInterface
{
    /**
     * 顯示日期格式
     * @param Carbon $date
     * @return string
     */
    public function showDateFormat(Carbon $date) : string;
}