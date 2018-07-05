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
    // 订单延迟key
    const ORDER_DELAY_KEY = 'QUEUES:DELAY:ORDER';

    // 支付异步key
    const PAY_NOTICE_KEY = 'QUEUES:PAY:NOTICE';

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
    public function returnUrl()
    {
        if (Request::isGet()) {
            $getData = Request::param();
            Log::error(get_current_date() . '-同步结果-' . json_encode($getData));
            return __METHOD__;
        }
    }

    /**
     * 异步通知
     * @return string
     */
    public function notifyUrl(PayService $payService)
    {
        $result = $payService->notifyUrl();
        if(!$result){
            Log::error('异步通知处理失败'.json_encode());
        }
        return $result;
    }

}