<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/6/21 21:43
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 支付信息处理
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\api\service;

use think\Db;
use think\Exception;
use think\facade\App;
use think\facade\Log;
use think\facade\Request;
use Yansongda\Pay\Pay;

class TransferService extends AbstractService
{
    protected $payService;
}