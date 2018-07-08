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
use app\common\controller\ApiController;
use app\common\library\repositories\eloquent\PayRepository;
use think\facade\App;
use think\facade\Log;

class GatewayController extends ApiController
{
    // 接口列表
    const API_LIST = [
      'pay.trade.web' => [PayService::class, 'web'], // 电脑支付
      'pay.trade.gateWay' => [PayService::class, 'gateWay'],
      'pay.trade.unPayWap' => [PayService::class, 'unPayWap'],
      'pay.trade.unQuickpay' => [PayService::class, 'unQuickpay'],
      'pay.trade.unQuickpay' => [PayService::class, 'unQuickpay'],
    ];

    /**
     * 支付网关 根据支付方式和渠道去找对应的渠道支付
     * @return string
     */
    public function payDo()
    {
        // 1、公共参数验证
        $post = $this->request->post();
        Log::error('公共参数验证------------' . json_encode($post));
        $data = [
          'mch_id' => 11111111111,
          'method' => 'pay.trade.web',
        ];
        Log::error('公共参数验证22------------' . static::API_LIST[$data['method']][0]);
        // 2、支付路由
        $routeControl = App::invokeClass(static::API_LIST[$data['method']][0]);
        $routeAction = static::API_LIST[$data['method']][1];
        Log::error('支付路由------------' . json_encode($routeControl));
        $result = $routeControl->$routeAction($data);
        Log::error('支付结果------------' . json_encode($result));
        if (!$result) {
            $error = $result->getError();
            Log::error(get_current_date() . ' 网关接口异步通知处理失败，错误原因: ' . json_encode($error));
            return json($error);
        }
        return $result;
    }

    /**
     * 同步通知
     * @return string
     */
    public function returnUrl(PayRepository $payRepository)
    {
        $result = $payRepository->returnUrl();
        if ($result) {
            if ($result['status'] == 1) {
                $this->success('支付成功', '', [], -1);
            } else {
                $this->error('支付失败', '', [], -1);
            }
        } else {
            $error = $payRepository->getError();
            Log::error(get_current_date() . ' 网关接口异步通知处理失败，错误原因: ' . json_encode($error));
            return json($error);
        }
    }

    /**
     * 异步通知
     * @return string
     */
    public function notifyUrl(PayRepository $payRepository)
    {
        $result = $payRepository->notifyUrl();
        if (!$result) {
            $error = $payRepository->getError();
            Log::error(get_current_date() . ' 网关接口异步通知处理失败，错误原因: ' . json_encode($error));
        }
        Log::debug(get_current_date() . ' [5] 网关接口异步通知处理成功 ');
        return $result;
    }

}