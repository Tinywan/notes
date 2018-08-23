<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/8/21 15:51
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 支付方式抽象类
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\common\services\contracts;

abstract class PaymentServiceAbstract implements PaymentServiceInterface
{
    public function unPayWap($params)
    {
        // TODO: Implement unPayWap() method.
    }

    public function wxGzh($params)
    {
        // TODO: Implement wxGzh() method.
    }

    public function unQuickPay($params)
    {
        // TODO: Implement unQuickPay() method.
    }

    public function wxScanCode($params)
    {
        // TODO: Implement wxScanCode() method.
    }

    public function qqScanCode($params)
    {
        // TODO: Implement qqScanCode() method.
    }

    public function agentPay($params)
    {
        // TODO: Implement agentPay() method.
    }

    public function transferPay($params)
    {
        // TODO: Implement transferPay() method.
    }

    /**
     * 向网关返回信息
     * @param $success
     * @param string $msg
     * @param int $errorCode
     * @param array $data
     * @return array
     */
    protected function returnData($success, $msg = '', $errorCode= 200, $data = [])
    {
        return [
            'success' => $success,
            'msg' => $msg,
            'errorCode' => $errorCode,
            'data' => $data
        ];
    }
}