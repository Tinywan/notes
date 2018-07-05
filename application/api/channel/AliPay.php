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
        $alipay = Pay::alipay(config('pay.alipay'));
        try {
            // 第四步 使用RSA的验签方法，通过签名字符串、签名参数（经过base64解码）及支付宝公钥验证签名
            $res = $alipay->verify($postData);
            Log::debug(' Alipay notify', $res->all());
        } catch (Exception $e) {
            return json([
              'code' => 500,
              'msg' => $e->getMessage()
            ]);
        }
        // 加入队列
        return $alipay->success()->send();
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
     * 异步通知处理
     * @param $data
     * @return $this|mixed
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

        // 交易状态 trade_status 成功，具体的业务信息在这里处理
        if ($data['trade_status'] == "TRADE_SUCCESS") {
            Log::debug(get_current_date() . ' [3] 订单业务逻辑处理完成 ' . json_encode($data));
            return $alipay->success()->send();
        }
        return $this->setReturnMsg(false, '[3] 未能识别的订单类型或状态 ' . json_encode($data));
    }
}