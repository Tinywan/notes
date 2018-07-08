<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/6/7 9:39
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 和壹付平台支付通道
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\common\library\repositories\channel;


use app\common\library\repositories\eloquent\ChannelAbstractRepository;
use think\Facade\Log;

class Saas extends ChannelAbstractRepository
{
    // 渠道配置
    protected $channelId = 9;

    // 设置渠道id
    public function setChannelId()
    {
        return $this->channelId;
    }

    // 微信公众号
    public function wxGzh($option)
    {
        // TODO: Implement wxGzh() method.
    }

    // 微信扫码
    public function wxSm($option)
    {
        // TODO: Implement wxSm() method.
    }

    // 微信H5
    public function wxH5($option)
    {
        // TODO: Implement wxH5() method.
    }

    // 支付宝扫码
    public function aliSm($option)
    {
        // TODO: Implement aliSm() method.
    }

    // 支付宝h5
    public function aliH5($option)
    {
        // TODO: Implement aliH5() method.
    }

    // 支付宝wap
    public function aliWap($option)
    {
        // TODO: Implement aliWap() method.
    }

    // 银行网银网关
    public function gateWay($option)
    {
        // 参数验证
//        if (!$this->vaildate($option, [
//          'goods' => 'require',
//          'order_no' => 'require',
//          'total_fee' => 'require|number|egt:0.01',
//          'bank_code' => 'require',
//          'client' => 'require',
//          'client_ip' => 'require',
//        ], [
//          'goods' => '商品名',
//          'order_no' => '订单号',
//          'total_fee' => '订单金额',
//          'bank_code' => '银行编码',
//          'client' => '客户端类型',
//          'client_ip' => '客户端IP',
//        ])
//        ) {
//            return false;
//        }

        #银行编码
        $bankCode = $option['bank_code'];
        #商品信息
        $memberGoods = $option['goods'];
        #异步通知地址
        $noticeSysaddress = $this->notifyUrl;
        #同步跳转地址
        $noticeWebaddress = $this->returnUrl;
        # 产品编码
        $productNo = 'EBANK-JS';
        #订单金额
        $requestAmount = $option['total_fee'];
        #商户编号
        $trxMerchantNo = $this->mchChannelConfig->channel_mch_id;
        #商户订单号
        $trxMerchantOrderno = $option['order_no'];

        $hmac = $this->getReqHmacString($bankCode, $memberGoods, $noticeSysaddress, $noticeWebaddress,
          $productNo, $requestAmount, $trxMerchantNo, $trxMerchantOrderno);
        $_data = array(
          'bankCode' => $bankCode,
          'memberGoods' => $memberGoods,
          'noticeSysaddress' => $noticeSysaddress,
          'noticeWebaddress' => $noticeWebaddress,
          'productNo' => $productNo,
          'requestAmount' => $requestAmount,
          'trxMerchantNo' => $trxMerchantNo,
          'trxMerchantOrderno' => $trxMerchantOrderno,
          'hmac' => $hmac
        );

        #下单地址
        $res = $this->send_post($this->payConfig->gateway, $_data);
        $resArr = json_decode($res, true);
        // Bug 解决 2018-06-19
        if ($resArr['code'] != '000') {
            return $this->setError(false, $resArr['message']);
        }
        $md5Str = "code={$resArr['code']}&message={$resArr['message']}&payUrl={$resArr['payUrl']}&trxMerchantNo={$resArr['trxMerchantNo']}&key=" . $this->mchChannelConfig->channel_mch_key;
        $sign = md5($md5Str);
        // 同步信息
        if ($resArr['code'] == '000' && $resArr['hmac'] == $sign) {
            return [
              'channel_order_no' => '',
              'order_no' => $trxMerchantOrderno,
              'pay_url' => $resArr['payUrl']
            ];
        } else {
            return $this->setError(false, '平台方错误，订单创建异常！');
        }
    }

    // 银行快捷支付
    public function unQuickpay($option)
    {

    }

    // 银联wap
    public function unPayWap($option)
    {
        // TODO: Implement unPayWap() method.
    }

    // qq扫码
    public function qqSm($option)
    {
        // TODO: Implement qqSm() method.
    }

    // 异步通知
    public function notify($data)
    {
        if (isset($data['trxMerchantOrderno'])) {
            // 支付
            $memberGoods = urldecode($data['memberGoods']);
            $signStr = "reCode={$data['reCode']}&trxMerchantNo={$data['trxMerchantNo']}&trxMerchantOrderno={$data['trxMerchantOrderno']}&result={$data['result']}&productNo={$data['productNo']}&memberGoods={$memberGoods}&amount={$data['amount']}&key=" . $this->mchChannelConfig->channel_mch_key;
            $sign = md5($signStr);
            // 签名check
            if ($sign != $data['hmac']) {
                return $this->setError(false, '支付签名验证失败');
            }
            $result = [
              'total_fee' => $data['amount'],
              'channel_order_no' => $data['trxMerchantNo'],
              'order_no' => $data['trxMerchantOrderno']
            ];
            if ($data['reCode'] == 1 && ($data['result'] == 'SUCCESS')) {
                return array_merge($result, ['status' => 'success']);
            } elseif ($data['reCode'] == 1 && ($data['result'] == 'FAIL')) {
                return array_merge($result, ['status' => 'fail']);
            } elseif ($data['reCode'] == 1 && ($data['result'] == 'DEAL')) {
                return array_merge($result, ['status' => 'wait']);
            }
        } elseif (isset($data['merchantOrderNo'])) {
            // 代付提现
            $signStr = "reCode={$data['reCode']}&merchantNo={$data['merchantNo']}&merchantOrderNo={$data['merchantOrderNo']}&result={$data['result']}&amount={$data['amount']}&key=" . $this->mchChannelConfig->channel_mch_key;
            $sign = md5($signStr);
            // 签名check
            if ($sign != $data['hmac']) {
                return $this->setError(false, '提现签名验证失败');
            }

            $result = [
              'total_fee' => $data['amount'],
              'channel_order_no' => $data['merchantNo'],
              'order_no' => $data['merchantOrderNo']
            ];

            if ($data['reCode'] == 1 && ($data['result'] == 'SUCCESS')) {
                return array_merge($result, ['status' => 'success']);
            } elseif ($data['reCode'] == 0 && ($data['result'] == 'FAIL')) {
                return array_merge($result, ['status' => 'fail']);
            }
        }
        return $this->setError(false, '未能识别的订单类型或状态=》' . json_encode($data));
    }

    // 账户金额查询
    public function balance($option)
    {
        // TODO: Implement balance() method.
    }

    // 异步通知成功处理响应信息【第三方通道接口要求的服务器通知回写，如：必须回写大写“SUCCESS”，否则重复通知4次】
    public function notifySuccessEcho($mch_order_no)
    {
        return 'SUCCESS';
    }

    // 提现
    public function cash($option)
    {
        if (!$this->vaildate($option, [
          'acc_attr' => 'require',
          'order_no' => 'require',
          'acc_bank_code' => 'require',
          'acc_card' => 'require',
          'acc_name' => 'require',
          'amount' => 'require',
          'acc_province' => 'require',
          'acc_city' => 'require',
          'acc_subbranch' => 'require',
        ], [
          'acc_attr' => '银行卡类型',
          'order_no' => '订单号',
          'acc_bank_code' => '银行编号',
          'acc_card' => '银行卡号',
          'acc_name' => '持卡人姓名',
          'amount' => '提现金额',
          'acc_province' => '省份',
          'acc_city' => '城市',
          'acc_subbranch' => '开户行支行名称',
        ])
        ) {
            return false;
        }

        // bankAccountName=xx&bankAccountNo=622588012672414611111&bankCode=CMBCHINA&city=110000&feeType=PAY&merchantNo=800566000005&merchantOrderNo=REMIT1511430489046&merproductNo=DAILYSTTLE&noticeAddress= http://www.baidu.com&phoneNo=11111&province=110000&remitAmount=0.01&remitRemark= 我是备注你好&key=RwO06T1145Bo1621y831A41c16UOv515CO522qR6m27354wgG0V4B903c0fL

        $url = "http://saas.yeeyk.com/saas-trx-gateway/dailyorder/accept";
        $bankAccountName = gbk_utf8($option['acc_name']); // 收款账户姓名
        $bankAccountNo = $option['acc_card']; // 收款银行账户
        $bankCode = $option['acc_bank_code']; // 开户银行编码
        $city = $option['acc_city']; // 开户市编码
        $feeType = 'PAY'; // 手续费承担方, 付款方:PAY  收款方:RECEIVE
        $merchantNo = $this->mchChannelConfig->channel_mch_id; // 商户编号, 请注意此商编必须为商户收款产品商编
        $merchantOrderNo = $option['order_no']; // 商户订单号
        $merproductNo = 'DAILYSTTLE'; // 代付产品 DAILYSTTLE("日结通") DEBITNOCARDPAY("代付代发")
        $noticeAddress = $this->notifyUrl; // 出款结果通知地址
        $phoneNo = $option['acc_mobile']; // 操作员手机号
        $province = $option['acc_province']; // 开户省编码
        $remitAmount = $option['amount']; // 出款金额
        $remitRemark = 'sasa-test'; // 出款备注
        $key = $this->mchChannelConfig->channel_mch_key;
        $str = "bankAccountName={$bankAccountName}&bankAccountNo={$bankAccountNo}&bankCode={$bankCode}&city={$city}" .
          "&feeType={$feeType}&merchantNo={$merchantNo}&merchantOrderNo={$merchantOrderNo}" .
          "&merproductNo={$merproductNo}&noticeAddress={$noticeAddress}&phoneNo={$phoneNo}&province={$province}" .
          "&remitAmount={$remitAmount}&remitRemark={$remitRemark}";
        //签名数据
        $signStr = $str . "&key=" . $key;
        //$signStr = strtolower($signStr); // 文档是小写，结果不是小写
        $hmac = md5($signStr);
        $reqData = [
          'bankAccountName' => $bankAccountName,
          'bankAccountNo' => $bankAccountNo,
          'bankCode' => $bankCode,
          'city' => $city,
          'feeType' => $feeType,
          'merchantNo' => $merchantNo,
          'merchantOrderNo' => $merchantOrderNo,
          'merproductNo' => $merproductNo,
          'noticeAddress' => $noticeAddress,
          'phoneNo' => $phoneNo,
          'province' => $province,
          'remitAmount' => $remitAmount,
          'remitRemark' => $remitRemark,
          'hmac' => $hmac
        ];
        $res = $this->send_post($url, $reqData);
        $result = json_decode($res, true);
        // 00000:收单成功 10001:参数有误或者配置有误 10002:系统错误 10004:订单号重复
        if ($result['code'] != '00000') {
            return $this->setError(false, $result['message'], $result['code']);
        }

        return [
          'channel_order_no' => $merchantNo,
          'order_no' => $option['order_no'],
        ];
    }

    // 提现查询
    public function cashQuery($option, $channel_order_no = '')
    {
        // TODO: Implement cashQuery() method.
    }

    #下单签名方法
    public function getReqHmacString($bankCode, $memberGoods, $noticeSysaddress, $noticeWebaddress,
                                     $productNo, $requestAmount, $trxMerchantNo, $trxMerchantOrderno)
    {
        $sbOld = "";
        if (false === empty($bankCode)) {
            $sbOld = $sbOld . "bankCode=" . $bankCode . "&";
        }
        $sbOld = $sbOld . "memberGoods=" . $memberGoods . "&";
        $sbOld = $sbOld . "noticeSysaddress=" . $noticeSysaddress . "&";
        if (false === empty($noticeWebaddress)) {
            $sbOld = $sbOld . "noticeWebaddress=" . $noticeWebaddress . "&";
        }
        $sbOld = $sbOld . "productNo=" . $productNo . "&";
        $sbOld = $sbOld . "requestAmount=" . $requestAmount . "&";
        $sbOld = $sbOld . "trxMerchantNo=" . $trxMerchantNo . "&";
        $sbOld = $sbOld . "trxMerchantOrderno=" . $trxMerchantOrderno . "&";
        $sbOld = $sbOld . "key=" . $this->mchChannelConfig->channel_mch_key;
        return md5($sbOld);
    }

    /**
     * 发送post请求
     * @param string $url 请求地址
     * @param array $post_data post键值对数据
     * @return string
     */
    public function send_post($url, $post_data)
    {
        Log::info('POST请求接口数据=》' . json_encode($post_data));
        $postdata = http_build_query($post_data);
        $options = array(
          'http' => array(
            'method' => 'POST',
            'header' => 'Content-type:application/x-www-form-urlencoded',
            'content' => $postdata,
            'timeout' => 15 * 60 // 超时时间（单位:s）
          )
        );
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        Log::info('POST请求接口返回数据=》' . json_encode($post_data));
        return $result;
    }

    public function notifySuccess()
    {

    }

    public function notifyUrl($data)
    {

    }
}