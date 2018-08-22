<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/8/21 15:51
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 支付方式服务 (业务逻辑在这里处理)
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\common\services\payment;

use app\common\model\Merchant;
use app\common\repositories\contracts\ChannelRepositoryInterface;
use app\common\services\contracts\PaymentServiceAbstract;
use think\facade\Log;

/**
 * Service 处理商业逻辑，然后注入到controller
 * Class PaymentService
 * @package app\common\services\payment
 */
class PaymentService extends PaymentServiceAbstract
{
    /**
     * 渠道仓库接口
     * @var ChannelRepositoryInterface
     */
    protected $channelRepository;

    /**
     * 网关接口列表
     * @var
     */
    protected $methodList;

    /**
     * 网关支付方式
     * @var
     */
    protected $paymentMethod;
    /**
     * Repository 注入到service
     */
    public function __construct(ChannelRepositoryInterface $channelRepository)
    {
        $this->channelRepository = $channelRepository;
        $this->methodList = config("api_method_list");
        $this->paymentMethod = config("payment_method");
    }
    /**
     * 渠道支付
     * @param $data
     * @return array
     * @throws \think\Exception\DbException
     */
    public function channelPay($data)
    {
        Log::debug('[渠道支付服务参数] '.json_encode($data));
        // 1、查询商户信息
        $mchInfo = Merchant::get($data['mch_id']);
//        if(empty($mchInfo)) {
//            return $this->returnData(false,"商户存在");
//        }

        // 2、查询支付方式信息
        $method = $this->methodList[$data['method']];
        Log::debug('[当前支付方式] '.$method);
//        if(!in_array($method,$this->paymentMethod)){
//            return $this->returnData(false,"非法的支付方式");
//        }

        // 3、查询系统支付方式总开关
        // 4、查询商户的支付方式配置
        // 5、选择支付渠道
        // 6、选择支付方式
//        if (empty($option['notify_url'])){
//            return $this->returnData(false, '异步通知地址不能为空！');
//        }
//        if (empty($option['return_url'])){
//            return $this->returnData(false, '同步跳转地址不能为空！');
//        }
        $channelData = [
            'mch_id'=>$data['mch_id'],
            'method'=>$method,
            'channel'=>'sandPay',
        ];
        //$result = $this->channelRepository->gateWay($channelData);
//        if ($result){
//            return $this->returnData(true, '订单创建成功！', 200,$result);
//        }else{
//            // 渠道错误信息
//            $error = $this->channelRepository->getError();
//            return $this->returnData(false,$error['msg'], $error['errorCode'],  $error['data']);
//        }
    }

    // 网关
    public function gateWay($params)
    {
        $this->channelRepository->gateWay($params);
    }

    public function unQuickPay($params)
    {
        $this->channelRepository->unQuickPay($params);
    }

    public function unPayWap($params)
    {
        $this->channelRepository->unPayWap($params);
    }



}