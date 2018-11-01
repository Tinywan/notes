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

use app\api\exception\GatewayException;
use app\api\service\AgentService;
use app\api\service\PayService;
use app\common\controller\ApiController;
use app\common\library\repositories\eloquent\PayRepository;
use app\common\model\Merchant;
use app\common\model\MerchantSubmch;
use think\Exception;
use think\facade\App;
use think\facade\Log;
use think\facade\Response;
use think\facade\Validate;

class Gateway extends ApiController
{
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
        'alipay.trade.web' => [\app\api\service\AliPayService::class, 'web'],
    ];

    /**
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function payDo()
    {
        $postData = $this->request->post();
        Log::debug('[网关] 接受参数：' . json_encode($postData));
        $validate = Validate::make([
            'mch_id' => 'require',
            'method' => 'require',
            'version' => 'require',
            'timestamp' => 'require',
            'content' => 'require',
            'sign' => 'require',
        ], [], [
            'mch_id' => '主商户ID',
            'method' => 'api名称',
            'version' => '版本号',
            'timestamp' => '当前时间戳',
            'content' => '请求参数',
            'sign' => '签名',
        ]);

        foreach ($postData as &$item) {
            $item = urldecode($item);
        }
        unset($item);
        if (!$validate->check($postData)) {
            return jsonResponse(1001, $validate->getError());
        }

        $signData = [
            'mch_id' => $postData['mch_id'],
            'sub_mch_id' => $postData['sub_mch_id'],
            'method' => $postData['method'],
            'version' => $postData['version'],
            'timestamp' => $postData['timestamp'],
            'content' => $postData['content'],
            'sign' => $postData['sign'],
        ];
        // 是否子账户
        if (!empty($postData['sub_mch_id'])) {
            $id = $postData['sub_mch_id'];
            $cacheKey = get_cache_key('MerchantSubmch', $id);
            $merchant = MerchantSubmch::where(['id' => $id, 'mch_id' => $postData['mch_id']])
                ->cache($cacheKey, self::CACHE_EXPIRE)
                ->find();
            if (!$merchant) {
                return jsonResponse(40007, self::errorList[40007]);
            }
        } else {
            $id = $postData['mch_id'];
            $cacheKey = get_cache_key('Merchant', $id);
            $merchant = Merchant::where(['id' => $id])
                ->cache($cacheKey, self::CACHE_EXPIRE)
                ->find();
            if (!$merchant) {
                return jsonResponse(40004, self::errorList[40004]);
            }
        }
        if (!self::verifySign($signData, $merchant->key)) {
            return jsonResponse(40003, self::errorList[40003]);
        }
        if (empty(self::API_LIST[$postData['method']])) {
            return jsonResponse(40002, self::errorList[40002]);
        }

        try {
            $routeControl = App::invokeClass(static::API_LIST[$postData['method']][0]);
            $routeAction = static::API_LIST[$postData['method']][1];
            $reqContent = json_decode($postData['content'], true);
            $reqContent['mch_id'] = $postData['mch_id'];
            $reqContent['sub_mch_id'] = $postData['sub_mch_id'] ?? '';
            $resp = $routeControl->$routeAction($reqContent);
            if (!$resp['success']) {
                Log::error('[网关] 失败: ' . json_encode($resp));
                return jsonResponse(-1, $resp['message'], $resp['data']);
            } else {
                Log::debug('[网关] 成功: ' . json_encode($resp));
                return jsonResponse(0, $resp['message'], $resp['data']);
            }
        } catch (Exception $e) {
            Log::error('[网关] 异常: ' . $e->getMessage() . '=' . json_encode($e->getTrace()));
            return jsonResponse(-1, self::errorList[-1]);
        }
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
            'method' => 'agents.trade.pay',
        ];
        // 2、支付服务路由
        $routeControl = App::invokeClass(static::API_SERVICE_LIST[$data['method']][0]);
        $routeAction = static::API_SERVICE_LIST[$data['method']][1];
        $data['payment'] = $routeAction;
        $result = $routeControl->$routeAction($data);
        if (!$result['success']) {
            Log::error('网关接口异步通知处理失败，错误原因: ' . json_encode($result));
            throw new GatewayException(['msg'=>$result['msg']]);
        }
        $msg = $result['msg'] ?? '成功1111';
        return self::responseJson(true, $msg, $result['errorCode'], $result['data']);
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