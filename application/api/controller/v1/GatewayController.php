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

use app\api\service\AgentService;
use app\api\service\PayService;
use app\common\controller\ApiController;
use app\common\library\repositories\eloquent\PayRepository;
use app\common\model\Agents;
use app\common\repositories\channel\HeePay;
use app\common\repositories\channel\SandPay;
use app\common\repositories\contracts\ChannelRepositoryInterface;
use app\common\services\payment\PaymentService;
use think\Container;
use think\facade\App;
use think\facade\Log;
use think\facade\Response;

class GatewayController extends ApiController
{
    private $_apiServiceClass = null;
    // 接口名称
    const API_LIST = [
        'pay.trade.web' => [PayService::class, 'web'],
        'pay.trade.gateWay' => [PayService::class, 'gateWay'],
        'pay.trade.unPayWap' => [PayService::class, 'unPayWap'],
        'pay.trade.unQuickpay' => [PayService::class, 'unQuickpay'],
        'agents.trade.pay' => [AgentService::class, 'pay'],
        'agents.trade.cash' => [AgentService::class, 'cash'],
    ];

    // 服务接口列表
    const API_SERVICE_LIST = [
        'pay.trade.web' => [\app\api\service\PaymentService::class, 'web'],
        'pay.trade.gateWay' => [\app\api\service\PaymentService::class, 'gateWay'],
        'pay.trade.unPayWap' => [\app\api\service\PaymentService::class, 'unPayWap'],
        'pay.trade.unQuickpay' => [\app\api\service\PaymentService::class, 'unQuickpay'],
        'agents.trade.pay' => [\app\api\service\AgentService::class, 'pay'],
        'agents.trade.cash' => [\app\api\service\AgentService::class, 'cash'],
    ];

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
     * @return \think\response
     */
    public function payDoNew()
    {
        // 1、公共参数验证
        $post = $this->request->post();
        Log::debug("[新支付网关]" . json_encode($post));
        $data = [
            'mch_id' => 12001,
            'method' => 'pay.trade.gateWay',
        ];
        Log::debug("[新支付网关] Class " . static::API_SERVICE_LIST[$data['method']][0]);
        Log::debug("[新支付网关] Action " . static::API_SERVICE_LIST[$data['method']][1]);
        // 2、支付服务路由
        $routeControl = App::invokeClass(static::API_SERVICE_LIST[$data['method']][0]);
        $routeAction = static::API_SERVICE_LIST[$data['method']][1];
        $data['payment'] = $routeAction;
        $result = $routeControl->$routeAction($data);
        if ($result['success']) {
            $msg = $result['msg'] ?? '成功1111';
            return self::responseJson(true, $msg, $result['errorCode'], $result['data']);
        } else {
            Log::error(' 网关接口异步通知处理失败，错误原因: ' . json_encode($result));
            return self::responseJson(false, $result['msg'], $result['errorCode']);
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

    /**
     * 错误代码
     */
    const errorList = [
        '0' => '成功',
        '-1' => '其它错误',
        '-1000' => '系统异常，请稍后再试',
        '-1001' => '参数错误',
        '-1002' => 'API不存在',
        '-1003' => '签名错误',
        '-1004' => '商户不存在',
        '-1005' => '商户已被停用',
        '-1006' => '商户资料未认证',
        '-1007' => 'API无权限',
    ];

    /**
     * 接口相应信息
     * @param $success
     * @param string $msg
     * @param int $errorCode
     * @param array $data
     * @return \think\response
     */
    private static function responseJson($success, $msg = '', $errorCode = 0, array $data = [])
    {
        $result = [
            'success' => $success,
            'msg' => $msg,
            'errorCode' => $errorCode,
            'data' => $data
        ];
        Log::debug('[接口返回信息]=>' . json_encode($result));
        return Response::create($result, $type = 'json', $code = 200);
    }

}