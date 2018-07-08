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

use app\api\repositories\PayRepository;

class PayService extends AbstractService
{
    // 支付渠道实例
    protected $payChannelRepository;

    public function __construct(PayRepository $payChannelRepository)
    {
        $this->payChannelRepository = $payChannelRepository;
    }

    /**
     * web支付
     */
    public function web($params)
    {
        $result = $this->payChannelRepository->pay(__FUNCTION__,$params);
        if ($result) {
            return $this->returnData(true, '订单创建成功！', 0, $result);
        } else {
            $error = $this->payChannelRepository->getError();
            return $this->returnData(false, $error['errorCode'], $error['msg'], $error['data']);
        }
    }

}