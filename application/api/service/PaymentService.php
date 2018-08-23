<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/8/23 11:07
 * |  Mail: 756684177@qq.com
 * |  Desc: 支付服务
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\api\service;


use app\common\model\Merchant;
use app\common\repositories\contracts\ChannelRepositoryInterface;
use think\Container;
use think\facade\Log;

class PaymentService extends BaseService
{
    // 网关接口列表
    protected $methodList;

    // 网关支付方式
    protected $paymentMethod;

    // 渠道类
    protected $channelClass;

    public function __construct()
    {
        $this->methodList = config("api_method_list");
        $this->paymentMethod = config("payment_method");
        $this->channelClass = config("channel_class");
    }

    /**
     * 网关支付
     * @param $params
     * @return array
     */
    public function gateWay($params)
    {
        $mch_info = $this->checkMerchantInfo($params);
        if($mch_info['success']){
            return $this->returnData(fail,$mch_info['msg']);
        }
        $method = $this->methodList[$params['method']];
        Log::debug('[当前支付方式] '.$method);
        Container::set(ChannelRepositoryInterface::class, $this->channelClass['heepay']);
        $result = app('PaymentServiceRepository')->$method($params);
        Log::debug("[PaymentService] 通道返回结果 ".json_encode($result));
        if ($result['success']){
            $msg = $result['msg']??'通道返回结果成功';
            return $this->returnData(true,$msg,$result['errorCode'], $result['data']);
        }else{
            Log::error('[PaymentService]通道返回结果失败 : ' . json_encode($result));
            return $this->returnData(false, $result['msg'],$result['errorCode']);
        }
    }

    /**
     * 银联wap
     */
    public function unPayWap($params)
    {

    }

    /**
     * 商户公共参数信息
     */
    public function checkMerchantInfo($param)
    {
        Log::debug("[PaymentService] 参数是 ".json_encode($param)); // {"mch_id":12001,"method":"pay.trade.gateWay"}
        // 参数验证操作
        //查询商户信息
        $this->mchInfo = Merchant::get($param['mch_id']);

        //查询支付方式信息
        if (empty($this->paymentMethod[$param['payment']])){
            return $this->returnData(false, '支付方式不存在');
        }
        return ture;
//        $this->parmentConfig = PaymentService::get(['key' => $param['payment']]);
//        if (empty($this->parmentConfig)) {
//            return $this->returnData(false, '支付方式不存在');
//        }
//        //查询系统支付方式总开关
//        if ($this->parmentConfig->status == 0){
//            return $this->returnData(false, '支付方式未开启');
//        }
    }
}