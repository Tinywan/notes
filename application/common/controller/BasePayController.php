<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/6/3 21:11
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 支付基类控制器
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\common\controller;

use think\App;
use think\Controller;

class BasePayController extends Controller
{
    public function __construct(App $app = null)
    {
        parent::__construct($app);
    }
}
