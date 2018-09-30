<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/6/7 9:39
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 裕支付网关支付 http://www.yycpay.com
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\common\repositories\paychannel;

use app\common\repositories\contracts\ChannelRepositoryAbstract;
use think\facade\Log;

class YYCPay extends ChannelRepositoryAbstract
{
    protected $channelId = 15;

    public function setChannelId()
    {
        return $this->channelId;
    }

    // 微信公众号
    public function wxGzh($option)
    {
        return $this->setError(false, '支付方式未开放');
    }

    // 微信扫码
    public function wxSm($option)
    {
        return $this->setError(false, '支付方式未开放');
    }

    // 微信H5
    public function wxH5($option)
    {
        return $this->setError(false, '支付方式未开放');
    }

    /**
     * 支付宝扫码
     * @param $option
     * @return array|bool|mixed
     */
    public function aliSm($option)
    {
        Log::debug('[渠道] 支付宝扫码参数 ' . json_encode($option));
        if (!$this->vaildate($option, [
            'goods' => 'require',
            'order_no' => 'require',
            'total_fee' => 'require|number|egt:10',
        ], [
            'goods' => '商品名',
            'order_no' => '订单号',
            'total_fee' => '订单金额',
        ])) {
            return false;
        }

        $orderid = $option['order_no']; // 商户订单号
        $value = $option['total_fee']; // 订单金额
        $parter = $this->mchChannelConfig->channel_mch_id; // 用户编号
        $type = 'ALIPAY'; // 业务代码
        $callbackurl = $this->notifyUrl; // 后台通知地址
        $hrefbackurl = $this->returnUrl; // 前台页面通知地址
        $attach = $option['goods']; // 附加信息域

        $signStr = "parter={$parter}&type={$type}&orderid={$orderid}&callbackurl={$callbackurl}" . $this->mchChannelConfig->channel_mch_key;
        Log::debug('[渠道] 支付宝H5 - 签名字符串 ' . $signStr);
        $sign = md5($signStr); // 签名值
        Log::debug('[渠道] 支付宝H5 - 签名 ' . $sign);
        $_data = array(
            'orderid' => $orderid,
            'value' => $value,
            'parter' => $parter,
            'type' => $type,
            'callbackurl' => $callbackurl,
            'hrefbackurl' => $hrefbackurl,
            'sign' => $sign
        );
        $pay_url = url('/pay') . '?order_no=' . $option['order_no'];
        return [
            'channel_order_no' => '',
            'order_no' => $option['order_no'],
            'pay_url' => $pay_url,
            'pay_data' => $_data
        ];
    }

    /**
     * 支付宝H5
     * @param $option
     * @return array|bool|mixed
     */
    public function aliH5($option)
    {
        Log::debug('[渠道] 支付宝H5参数 ' . json_encode($option));
        if (!$this->vaildate($option, [
            'goods' => 'require',
            'order_no' => 'require',
            'total_fee' => 'require|number|egt:10',
        ], [
            'goods' => '商品名',
            'order_no' => '订单号',
            'total_fee' => '订单金额',
        ])) {
            return false;
        }

        $orderid = $option['order_no']; // 商户订单号
        $value = $option['total_fee']; // 订单金额
        $parter = $this->mchChannelConfig->channel_mch_id; // 用户编号
        $type = 'ALIWAP'; // 业务代码
        $callbackurl = $this->notifyUrl; // 后台通知地址
        $hrefbackurl = $this->returnUrl; // 前台页面通知地址
        $attach = '八月十五'; // 附加信息域

        $signStr = "parter={$parter}&type={$type}&orderid={$orderid}&callbackurl={$callbackurl}" . $this->mchChannelConfig->channel_mch_key;
        Log::debug('[渠道] 支付宝H5 - 签名字符串 ' . $signStr);
        $sign = md5($signStr); // 签名值
        Log::debug('[渠道] 支付宝H5 - 签名 ' . $sign);
        $_data = array(
            'orderid' => $orderid,
            'value' => $value,
            'parter' => $parter,
            'type' => $type,
            'callbackurl' => $callbackurl,
            'hrefbackurl' => $hrefbackurl,
            'sign' => $sign
        );
        $pay_url = url('/pay') . '?order_no=' . $option['order_no'];
        return [
            'channel_order_no' => '',
            'order_no' => $option['order_no'],
            'pay_url' => $pay_url,
            'pay_data' => $_data
        ];
    }

    // 支付宝wap
    public function aliWap($option)
    {
        return $this->setError(false, '支付方式未开放');
    }

    // 银行网银网关
    public function gateWay($option)
    {
        return $this->setError(false, '支付方式未开放');
    }

    // 银行快捷支付
    public function unQuickpay($option)
    {
        return $this->setError(false, '支付方式未开放');
    }

    // 银联wap
    public function unPayWap($option)
    {
        return $this->setError(false, '支付方式未开放');
    }

    // qq扫码
    public function qqSm($option)
    {
        return $this->setError(false, '支付方式未开放');
    }

    // 异步通知
    public function notify($data)
    {
        Log::debug('[渠道] 裕支付-异步接收参数 ' . json_encode($data));
        if (isset($data['ovalue']) && isset($data['restate'])) {
            Log::debug('[渠道] 支付异步开始处理... ');
            $signStr = "orderid={$data['orderid']}&restate={$data['restate']}&ovalue={$data['ovalue']}" . $this->mchChannelConfig->channel_mch_key;
            $sign = md5($signStr);
            if ($sign != $data['sign']) {
                Log::debug('[渠道] 支付签名验证失败 ');
                return $this->setError(false, '支付签名验证失败');
            }
            $result = [
                'total_fee' => $data['ovalue'],
                'channel_order_no' => $data['sysorderid'],
                'order_no' => $data['orderid']
            ];
            if ($data['restate'] == '0') {
                return array_merge($result, ['status' => 'success']);
            } else {
                return array_merge($result, ['status' => 'fail']);
            }
        } elseif (isset($data['amount']) && isset($data['payfee'])) {
            Log::debug('[渠道] 代付异步开始处理... ');
            $signStr = "amount={$data['amount']}&attach={$data['attach']}&orderid={$data['orderid']}&parter={$data['parter']}" .
                "&payamt={$data['payamt']}&payfee={$data['payfee']}&states={$data['states']}&sysorderid={$data['sysorderid']}&key=" . $this->mchChannelConfig->channel_mch_key;
            $sign = md5($signStr);
            if (!isset($data['sign'])) {
                return $this->setError(false, '签名字符串不存在');
            }
            if ($sign != $data['sign']) {
                Log::debug('[渠道] 代付异步通知签名验证失败 ');
                return $this->setError(false, '签名验证失败');
            }

            $result = [
                'remark' => gbk_utf8($data['smsg']),
                'total_fee' => $data['amount'],
                'channel_order_no' => $data['sysorderid'],
                'order_no' => $data['orderid'],
                'payfee' => $data['payfee']
            ];

            if ($data['states'] == '0') { // 等待处理
                return array_merge($result, ['status' => 'wait']);
            } elseif ($data['states'] == '1') { // 处理中
                return array_merge($result, ['status' => 'wait']);
            } elseif ($data['states'] == '2') { // 成功
                return array_merge($result, ['status' => 'success']);
            } elseif ($data['states'] == '3') { // 失败
                return array_merge($result, ['status' => 'fail']);
            } elseif ($data['states'] == '4') { // 退款
                return array_merge($result, ['status' => 'fail']);
            }
        }
        return $this->setError(false, '未能识别的订单类型或状态=》' . json_encode($data));
    }

    /**
     * 账户金额查询
     * @param $option
     * @return mixed|void
     */
    public function balance($option)
    {
        // TODO: Implement balance() method.
    }

    /**
     * 异步通知成功处理响应信息
     * @param $mch_order_no
     * @return mixed|string
     */
    public function notifySuccessResponse()
    {
        return 'Ok';
    }

    /**
     * 提现
     * @param $option
     * @return mixed
     */
    public function cash($option)
    {
        Log::debug('[渠道] 裕支付-提现参数：' . json_encode($option));
        if (!$this->vaildate($option, [
            'acc_attr' => 'require',
            'order_no' => 'require',
            'acc_card' => 'require',
            'acc_name' => 'require',
            'total_fee' => 'require|between:10,49999',
            'acc_province' => 'require',
            'acc_city' => 'require',
            'acc_subbranch' => 'require',
        ], [
            'acc_attr' => '银行卡类型',
            'order_no' => '订单号',
            'acc_card' => '银行卡号',
            'acc_name' => '持卡人姓名',
            'total_fee' => '提现总金额',
            'acc_province' => '省份',
            'acc_city' => '城市',
            'acc_subbranch' => '开户行支行名称',
        ])) {
            return false;
        }

        $orderid = $option['order_no']; // 商户订单号
        $parter = $this->mchChannelConfig->channel_mch_id; // 用户编号
        $amount = $option['amount']; // 订单金额
        $paytype = 'BillOrder'; // 交易类型
        $paymode = '0'; // 代付类型
        $payprod = 'D0'; // 代付产品
        $bankname = $option['acc_bank']; // 开户行名称
        $bankcode = $option['acc_card']; // 开户账号
        $payname = $option['acc_name']; // 开户人姓名
//        $bankpriv = $option['bankpriv']; // 开户分行省份 选填
//        $bankcity = $option['bankcity']; // 开户分行城市 选填
//        $bankpriv = $option['acc_subbranch']; // 开户分行名称 选填
        $callurl = $this->notifyUrl; // 后台通知地址
        $attach = 'Tinywan'; // 附加信息域

        $gb_bankname = utf8_gb2312($bankname);
        $gb_payname = utf8_gb2312($payname);
        $signStr = "amount={$amount}&attach={$attach}&bankcode={$bankcode}&bankname={$bankname}&callurl={$callurl}&orderid={$orderid}&parter={$parter}&paymode={$paymode}&payname={$payname}&payprod={$payprod}&paytype={$paytype}&key=" . $this->mchChannelConfig->channel_mch_key;
        $signStr = utf8_gb2312($signStr);
        Log::debug('[渠道] 提现 签名字符串 ' . $signStr);
        $sign = md5($signStr);
        Log::debug('[渠道] 提现 - 签名 ' . $sign);
        // 签名数据
        $reqData = [
            'attach' => utf8_gb2312($attach),
            'amount' => $amount,
            'bankcode' => $bankcode,
            'bankname' => $gb_bankname,
            'callurl' => $callurl,
            'orderid' => $orderid,
            'parter' => $parter,
            'paymode' => $paymode,
            'payname' => $gb_payname,
            'payprod' => $payprod,
            'paytype' => $paytype,
            'sign' => $sign
        ];
        $url = 'http://pay.yycshop.com/InterFace/ChargeBill.aspx';
        $res = curl_post($this->payConfig->gateway, $reqData);
        Log::debug('[渠道] 提现接口返回信息 ' . $res);
        $result = json_decode($res, true);
        if (isset($result['Err']) && isset($result['ErrMsg'])) {
            Log::error('[渠道] 提现请求失败 ' . urldecode($result['ErrMsg']));
            return $this->setError(false, urldecode($result['ErrMsg']));
        }
        if ($result['states'] == '1') {
            return [
                'channel_order_no' => $orderid,
                'order_no' => $option['order_no'],
                'message' => $result['smsg']
            ];
        } else {
            $msg = '平台方未知错误';
        }
        Log::debug('[渠道] 提现请求失败 ' . urldecode($result['smsg']));
        return $this->setError(false, $msg);
    }

    /**
     * 提现查询
     * @param $option
     * @param string $channel_order_no
     * @return array|bool|mixed
     */
    public function cashQuery($option, $channel_order_no = '')
    {
        Log::debug('[渠道] 提现查询参数：' . json_encode($option));
        $orderid = $option['order_no']; // 商户订单号
        $parter = $this->mchChannelConfig->channel_mch_id; // 用户编号
        $paytype = 'BillQuery'; // 交易类型
        $orddate = date("Y-m-d", time()); // 订单提交是的日期格式
        $signStr = "orddate={$orddate}&orderid={$orderid}&parter={$parter}&paytype={$paytype}&key=" . $this->mchChannelConfig->channel_mch_key;
        $sign = md5($signStr);
        $reqData = [
            'orderid' => $orderid,
            'parter' => $parter,
            'paytype' => $paytype,
            'orddate' => $orddate,
            'sign' => $sign
        ];
        $url = 'http://pay.yycshop.com/InterFace/ChargeBill.aspx';
        $res = curl_post($url, $reqData);
        $resArr = json_decode(gbk_utf8($res), true); // 这里比较坑
        if (isset($resArr['Err']) && isset($resArr['ErrMsg'])) {
            Log::error('[渠道] 提现查询请求失败 ' . urldecode($resArr['ErrMsg']));
            return $this->setError(false, urldecode($resArr['ErrMsg']));
        }
        $resData = [
            'channel_order_no' => $resArr['orderid'],
            'order_no' => $resArr['orderid'],
            'total_fee' => $resArr['amount'],//提现金额
            'fee' => $resArr['payfee'], //手续费
        ];
        switch ($resArr['states']) {  //状态:0:处理中,1:已支付,2:支付失败
            case 0:
                $resData['status'] = 0;
                $resData['remark'] = '等待处理，主动查询';
                break;
            case 1:
                $resData['status'] = 0;
                $resData['remark'] = '处理中，主动查询';
                break;
            case 2:
                $resData['status'] = 1;
                $resData['remark'] = '提现成功，主动查询';
                break;
            case 3:
                $resData['status'] = 2;
                $resData['remark'] = '提现失败，主动查询';
                break;
            case 4:
                $resData['status'] = 2;
                $resData['remark'] = '退款，主动查询';
                break;
            default:
                $resData['status'] = 2;
                $resData['remark'] = '接口查询没有对应的状态码';
        }
        Log::debug('[渠道] 提现返回处理信息 ' . json_encode($resData));
        return $resData;
    }
}