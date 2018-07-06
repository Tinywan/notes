<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/7/5 18:04
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 描述信息
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\api\channel;

use think\Exception;
use think\facade\Log;
use Yansongda\Pay\Pay;

class AliPay extends BaseChannel
{
    public function gateWay()
    {
        // 参数验证
        return __METHOD__;
    }

    /**
     * 扫码支付
     * @return string
     */
    public function scanCode()
    {
        return '扫码支付 ' . __METHOD__;
    }

    public function h5()
    {
        return 'h5支付' . __METHOD__;
    }

    public function wap()
    {
        return '支付宝wap ' . __METHOD__;
    }

    /**
     *
     * @param $data
     * @return $this|mixed
     */

    /**
     * @param $data
     * @return $this|mixed
     * @throws \Yansongda\Pay\Exceptions\InvalidSignException
     */
    public function notify($data)
    {
        // 参数签名验证
        $alipay = Pay::alipay(config('pay.alipay'));
        try {
            // 第四步 使用RSA的验签方法，通过签名字符串、签名参数（经过base64解码）及支付宝公钥验证签名
            $res = $alipay->verify($data);
            Log::debug('Alipay notify', $res->all());
        } catch (Exception $e) {
            return $this->setReturnMsg(false, '[3] 支付签名验证异常 ' . $e->getMessage());
        }

        $result = [
            'total_fee' => $data['total_amount'],
            'channel_order_no' => $data['trade_no'],
            'order_no' => $data['out_trade_no']
        ];
        // 交易状态 trade_status 成功，具体的业务信息在这里处理
        if ($data['trade_status'] == "TRADE_SUCCESS") {
            Log::debug(get_current_date() . ' [4] 交易支付成功 ' . json_encode($data));
            $result['status'] = 'success';
            return $result;
        } elseif ($data['trade_status'] == "TRADE_CLOSED") {
            Log::debug(get_current_date() . ' [4] 未付款交易超时关闭，或支付完成后全额退款 ' . json_encode($data));
            $result['status'] = 'fail';
            return $result;
        } elseif ($data['trade_status'] == "WAIT_BUYER_PAY") {
            Log::debug(get_current_date() . ' [4] 交易创建，等待买家付款 ' . json_encode($data));
            $result['status'] = 'wait';
            return $result;
        }
        return $this->setReturnMsg(false, '[3] 未能识别的订单类型或状态 ' . json_encode($data));
    }

    public function notifySuccess()
    {
        return "SUCCESS";
    }
}