<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/9/30 8:49
 * |  Mail: 756684177@qq.com
 * |  Desc: 描述信息
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\pay\controller;


use app\common\controller\PayController;
use think\facade\Log;
use think\Validate;

class DemoController extends PayController
{
    /**
     * 自定义金额
     * @return mixed
     */
    public function customAmount()
    {
        $mch_id = 1538269987;
        return $this->fetch('',[
            'mch_id'=>$mch_id,
        ]);
    }

    /**
     * 自定义金额处理
     */
    public function customAmountPay()
    {
        $postData = $this->request->post();
        $order_no = 'T' .date('ymdHis', time()) . rand(1000, 9999);
        $orderid = $order_no; // 商户订单号
        $value = $postData['price']; // 订单金额
        $parter = 20088; // 用户编号
        $type = 'ALIWAP'; // 业务代码
        $callbackurl = 'http://notes.frp.tinywan.top/api/v1/notify'; // 后台通知地址
        $hrefbackurl = 'http://notes.frp.tinywan.top/api/v1/return'; // 前台页面通知地址
        $key = '781B7D366F0C6E148394F4A3D52F982E';
        $signStr = "parter={$parter}&type={$type}&orderid={$orderid}&callbackurl={$callbackurl}" . $key;
        $sign = md5($signStr); // 签名值
        $_data = array(
            'orderid' => $orderid,
            'value' => $value,
            'parter' => $parter,
            'type' => $type,
            'callbackurl' => $callbackurl,
            'hrefbackurl' => $hrefbackurl,
            'sign' => $sign
        );
        $html = '<form name="form2" id="submit" action="http://pay.yycshop.com/interface/chargebank.aspx" method="post" style="display:none;">';
        foreach ($_data as $key => $val) {
            $html .= '<input type="hidden" name="' . $key . '" value="' . $val . '"><br/>';
        }
        $html .= '</form><script>document.forms[\'submit\'].submit();</script>';
        Log::debug('[支付跳转] 打印HTML ' . $html);
        exit($html);
    }
}