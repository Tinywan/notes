<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/6/12 10:19
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 选择业务订单跳转至支付网关
 * |  1、API 路由：在聚合支付场景下，当有多个支付产品可以提供支持时，使用支付网关可以让接入方对接时无需考虑支付产品的部署问题
 * |  2、接口安全：熔断、限流与隔离。这对支付服务来说尤为重要。这是微服务架构的基本功能，本文不做描述。
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\api\controller\v1;

use app\api\service\PayService;
use app\common\controller\ApiController;
use app\common\library\repositories\eloquent\PayRepository;
use app\common\services\payment\PaymentService;
use think\facade\App;
use think\facade\Log;

class GatewayController extends ApiController
{
    protected $paymentService;

    // 接口名称
    const API_LIST = [
        'pay.trade.web' => [PayService::class, 'web'], // 电脑支付
        'pay.trade.gateWay' => [PayService::class, 'gateWay'],
        'pay.trade.unPayWap' => [PayService::class, 'unPayWap'],
        'pay.trade.unQuickpay' => [PayService::class, 'unQuickpay'],
    ];
    /**
     * 注入service
     * GatewayController constructor.
     * @param PaymentService $paymentService
     */
    public function __construct(PaymentService $paymentService)
    {
        parent::__construct();
        $this->paymentService = $paymentService;
    }

    /**
     * 三方通道网关
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
        Log::debug('公共参数验证22------------' . static::API_LIST[$data['method']][0]);
        // 2、支付路由
        $routeControl = App::invokeClass(static::API_LIST[$data['method']][0]);
        $routeAction = static::API_LIST[$data['method']][1];
        Log::debug('支付方式------------' . json_encode($routeAction));
        $result = $routeControl->$routeAction($data);
        Log::debug('支付结果------------' . json_encode($result));
        if (!$result) {
            $error = $result->getError();
            Log::error(' 网关接口异步通知处理失败，错误原因: ' . json_encode($error));
            return json($error);
        }
        return json($result);
    }

    /**
     * 新支付网关
     * @return \think\response\Json
     * @throws \think\Exception\DbException
     */
    public function payDoNew()
    {
        // 1、公共参数验证
        $post = $this->request->param();
        Log::debug("[新支付网关]".json_encode($post));
        $data = [
            'mch_id' => 12001,
            'method' => 'pay.trade.gateWay',
        ];
        // 2、网关服务
        $result = $this->paymentService->channelPay($data);
        if (!$result['success']) {
            Log::error("[网关接口调用失败]".json_encode($result));
            return json($result);
        }else{
            Log::debug("[网关接口访问成功]");
            return json($result);
        }
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
            Log::error(' 网关接口异步通知处理失败，错误原因: ' . json_encode($error));
            return json($error);
        }
    }

    /**
     * 异步通知
     * @param PayRepository $payRepository
     * @return bool|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function notifyUrl(PayRepository $payRepository)
    {
        $result = $payRepository->notifyUrl();
        if (!$result) {
            $error = $payRepository->getError();
            Log::error(' 网关接口异步通知处理失败，错误原因: ' . json_encode($error));
            exit("fail");
        }
        Log::debug(' [5] 网关接口异步通知处理成功 ');
        exit("success");
    }

}