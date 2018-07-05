<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/6/12 10:19
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 选择业务订单跳转至支付网关
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\api\controller\v1;

use app\api\service\PayService;
use app\common\controller\BaseApiController;
use think\facade\Log;
use think\facade\Request;

class GatewayController extends BaseApiController
{
    public function payDo()
    {
        // 1、签证 - 创建支付订单
        // 1、接口适配
        return __METHOD__;
    }

    /**
     * 同步通知
     * @return string
     */
    public function returnUrl(PayService $payService)
    {
        $result = $payService->returnUrl();
        if (!$result) {
            $error = $payService->getError();
            Log::error(get_current_date() . ' 网关接口异步通知处理失败，错误原因: ' . json_encode($error));
        }
        return "支付成功";
    }

    /**
     * 异步通知
     * @return string
     */
    public function notifyUrl(PayService $payService)
    {
        $result = $payService->notifyUrl();
        if (!$result) {
            $error = $payService->getError();
            Log::error(get_current_date() . ' 网关接口异步通知处理失败，错误原因: ' . json_encode($error));
        }
        Log::debug(get_current_date() . ' 网关接口异步通知处理成功 ');
        return $result;
    }

}