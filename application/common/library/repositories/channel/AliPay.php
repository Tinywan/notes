<?php

/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/7/8 12:04
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 支付宝具体渠道配置
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\common\library\repositories\channel;

use app\common\library\repositories\eloquent\ChannelAbstractRepository;
use think\Exception;
use think\facade\Log;
use Yansongda\Pay\Pay;

class AliPay extends ChannelAbstractRepository
{
    public function aliSm($option)
    {
        // TODO: Implement aliSm() method.
    }

    /**
     * @return string
     */
    public function wxH5($option)
    {
        // TODO: Implement aliSm() method.
    }


    /**
     * Web网页支付
     * @return string
     */
    public function web($option)
    {
        Log::debug('Web网页支付===' . json_encode($option));
        $payOrder = [
          'out_trade_no' => $option['order_no'],
          'total_amount' => $option['total_fee'],
          'subject' => $option['goods'],
        ];
        $alipay = Pay::alipay(config('pay.alipay'));
        return $alipay->web($payOrder)->send();
    }

    /**
     * 同步
     * @param $data
     * @return array|mixed
     */
    public function returnUrl($data)
    {
        // 参数签名验证
        $alipay = Pay::alipay(config('pay.alipay'));
        try {
            $res = $alipay->verify($data);
            Log::debug(get_current_date() . ' [2] 支付同步签名验证 ');
        } catch (Exception $e) {
            return $this->setError(false, '[3] 支付签名验证异常 ' . $e->getMessage());
        }

        // 接口返回数据
        $result = [
          'status' => 1,
          'total_amount' => $data['total_amount'],
          'channel_order_no' => $data['trade_no'],
          'order_no' => $data['out_trade_no']
        ];
        Log::debug(get_current_date() . ' [3] 签名验证通过 ');
        return $result;
    }

    /**
     * 异步回调
     * @param $data
     * @return array|mixed
     */
    public function notifyUrl($data)
    {
        // 参数签名验证
        $alipay = Pay::alipay(config('pay.alipay'));
        try {
            // 第四步 使用RSA的验签方法，通过签名字符串、签名参数（经过base64解码）及支付宝公钥验证签名
            $res = $alipay->verify($data);
            Log::debug(get_current_date() . ' [3] 支付验证签名 ');
        } catch (Exception $e) {
            return $this->setError(false, '[3] 支付签名验证异常 ' . $e->getMessage());
        }

        $result = [
          'total_amount' => $data['total_amount'],
          'channel_order_no' => $data['trade_no'],
          'order_no' => $data['out_trade_no']
        ];
        // 交易状态 trade_status 成功，具体的业务信息在这里处理
        if ($data['trade_status'] == "TRADE_SUCCESS") {
            Log::debug(get_current_date() . ' [4] 交易支付成功 ');
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
        return $this->setError(false, '[3] 未能识别的订单类型或状态 ' . json_encode($data));
    }

    // 返回正确消息
    public function notifySuccess()
    {
        return "success";
    }
}