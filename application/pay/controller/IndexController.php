<?php
/** .-----------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |-------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/6/2 13:41
 * |  Mail: Overcome.wan@Gmail.com
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\pay\controller;

use app\common\controller\BasePayController;
use think\Db;
use think\facade\Log;

class IndexController extends BasePayController
{
    public function index()
    {
        Log::error('1'.__METHOD__);
        return "Hi ".__METHOD__;
    }

    public function sql()
    {
        $res = Db::name('order')->count();
        Log::debug(get_current_date().' SQL '.$res);
        return "Hi ".__METHOD__;
    }
}
