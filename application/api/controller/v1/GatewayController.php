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
use think\facade\App;
use think\facade\Log;

class GatewayController extends BaseApiController
{
    // 接口列表
    const API_LIST = [
        'pay.trade.gateWay' => [PayService::class, 'gateWay'],
        'pay.trade.unPayWap' => [PayService::class, 'unPayWap'],
        'pay.trade.unQuickpay' => [PayService::class, 'unQuickpay'],
    ];

    /**
     * 支付网关
     * @return string
     */
    public function payDo()
    {
        // 1、公共参数验证
        $post = $this->request->post();
        Log::debug('公共参数验证------------' . json_encode($post));
        $data = [
            'mch_id' => $post['mch_id'],
            'method' => $post['method'],
            'version' => $post['version'],
            'timestamp' => $post['timestamp'],
            'content' => $post['content'],
            'sign' => $post['sign'],
        ];
        // 2、支付路由
        $routeControl = App::invokeClass(static::API_LIST[$data['method'][0]]);
        $routeAction = static::API_LIST[$data['method'][1]];
        Log::debug('支付路由------------' . json_encode($routeControl));
        $result = $routeControl->$routeAction($data);
        Log::debug('支付结果------------' . json_encode($result));
        return $post;
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
            return json($error);
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