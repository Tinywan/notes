<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/8/23 10:58
 * |  Mail: 756684177@qq.com
 * |  Desc: 代付服务
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\api\service;


use app\common\library\daifu\Daifu;
use think\Db;
use think\Exception;
use think\facade\Log;
use think\facade\Validate;

class AgentService extends BaseService
{
    /**
     * 代付晚上10点到1点不能交易
     * @return bool
     */
    protected static function tradeTime()
    {
        $startTime = mktime(1, 00, 00, date('m'), date('d'), date('Y'));
        $endTime = mktime(22, 00, 00, date('m'), date('d'), date('Y'));
        $currentTime = time();
        if (!(($currentTime >= $startTime) && ($currentTime <= $endTime))) {
            return false;
        }
        return true;
    }

    /**
     * 新-旧异步通知共用
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function notifyUrl()
    {
        $params = request()->param();
        Log::debug('[异步通知] 参数 ' . json_encode($params));
        if (!request()->isPost()) {
            Log::error(' 非异步通知费POST ' . json_encode($params));
            return $this->returnData(false, ' 非异步通知费POST 请求', 0);
        }
        // 接口异步处理
        if (isset($params['dstbdata']) && isset($params['dstbdatasign'])) {
            $tmpArr = explode('&', $params['dstbdata']);
            $postData = [];
            foreach ($tmpArr as $value) {
                $tmp = explode('=', $value);
                $postData[$tmp[0]] = $tmp[1];
            }
            Log::debug('[异步通知] 字符串转换为数组 ' . json_encode($postData));
            $order_no = $postData['dsorderid'];
            if (empty($order_no)) {
                return $this->returnData(false, $order_no . '订单号不存在', 0);
            }

            if ($postData['orderid'] == null || $postData['orderid'] == '') {
                return $this->returnData(false, '非法的系统订单', 1);
            }

            $orderInfo = InterfaceOrder::where('order_no', '=', $order_no)->find();
            Log::debug('[异步通知] 订单信息 ' . json_encode($orderInfo));
            $paymentInfo = MerchantPaymentInterface::alias('a')
              ->field(['c.wallet_key', 'c.fee', 'a.payment_fee', 'a.id', 'a.payment_interface_id', 'c.id payment_id'])
              ->join('payment_interface c', 'a.payment_interface_id = c.id')
              ->where([
                'a.mch_id' => $orderInfo['mch_id'],
                'a.payment_interface_id' => $orderInfo['payment_interface_id']
              ])
              ->find();
            // 联合查询数据
            if (!$paymentInfo) {
                return $this->setError(false, $order_no . '没有代付商户信息', 0);
            }

            // 加密
            $des = new DesJava();
            $des->DES_JAVA($paymentInfo['wallet_key']);
            $strEncrypt = $des->encrypt($params['dstbdata']);
            // 签名验证
            if ($strEncrypt != $params['dstbdatasign']) {
                Log::error('[异步通知] 异步签名验证失败');
                return $this->setError(false, $order_no . '签名验证失败', 0);
            }

            //金额匹配验证
            if ($postData['amount'] != $orderInfo['price']) {
                Log::error('[异步通知] 订单金额与发起支付金额不一致');
                return $this->setError(false, '订单金额与发起支付金额不一致');
            }

            // 如果订单状态正在处理中
            if ($orderInfo['status'] == 1) {
                $updateData['id'] = $orderInfo->id;
                if (isset($postData['orderid'])) {
                    $updateData['channel_order_no'] = $postData['orderid'];
                }
                $updateData['pay_time'] = time();
                $updateData['remark'] = $postData['errtext'];
                if ($postData['returncode'] == '00') {
                    $updateData['status'] = 2;
                    $updateData['remark'] = '代付成功';
                } else {
                    $updateData['status'] = -1;
                }

                try {
                    Db::startTrans();
                    // 代付商户关系 加锁机制
                    $merchantPayment = Db::name('merchant_payment_interface')->where([
                      'mch_id' => $orderInfo['mch_id'],
                      'payment_interface_id' => $paymentInfo['payment_interface_id']
                    ])->lock(true)->find();

                    // 1、修改订单状态
                    Db::name('interface_order')->update($updateData);

                    // 利润 = 客户代付手续费 - 商户代付手续费 eg: 5-2 = 3
                    $profit = round(bcsub($merchantPayment['payment_fee'], $paymentInfo['fee'], 3), 2);
                    Log::debug('[异步通知] 利润 '.$profit);
                    // 交易成功 00 ，未代付成功则返回客户储备金余额
                    if ($postData['returncode'] != '00') {
                        Log::debug('[异步通知] 代付失败');
                        // 总代付费用（订单表）
                        $totalAmount = $orderInfo['total_fee'];
                        Log::debug('[异步通知] 总代付费用 '.$totalAmount);
                        $merchantTotalFee = bcadd($merchantPayment['payment_reserve'], $totalAmount, 2);
                        Log::debug('[异步通知] 总代备付金 '.$merchantTotalFee);
                        // 储备金增加
                        Db::name('merchant_payment_interface')
                          ->where('id', '=', $merchantPayment['id'])
                          ->setInc('payment_reserve', $totalAmount);

                        // 6-1、代付中利润减少
                        Db::name('payment_interface')
                          ->where('id', '=', $paymentInfo['payment_id'])
                          ->setDec('cash_profit', $profit);

                        // 6-2、总利润减少
                        Db::name('payment_interface')
                          ->where('id', '=', $paymentInfo['payment_id'])
                          ->setDec('all_profit', $profit);

                        // 6-20、总代付商户备付金增加
                        Db::name('payment_interface')
                          ->where('id', '=', $merchantPayment['payment_interface_id'])
                          ->setInc('total_payment_reserve', $totalAmount);

                        // 账户均衡表
                        Db::name('payment_interface_balance_record')->insert([
                          'mch_id' => $orderInfo['mch_id'],
                          'order_no' => $order_no,
                          'payment_interface_id' => $paymentInfo['payment_interface_id'],
                          'trade_merchant' => $orderInfo['mch_id'],
                          'trade_adversary' => json_encode($postData),
                          'trade_channel' => json_encode($paymentInfo),
                          'record_type' => 1,
                          'type' => 1,
                          'money' => $totalAmount,
                          'before_money' => $merchantPayment['payment_reserve'],
                          'after_money' => $merchantTotalFee,
                          'remark' => '商户-' . $orderInfo['mch_id'] . '|入账-' . $totalAmount . '元',
                          'created_at' => time()
                        ]);
                    } elseif ($postData['returncode'] == '00') {
                        Log::debug('[异步通知] 代付成功');
                        // 代付中利润添加到总利润中
                        $interface = Db::name('payment_interface')
                          ->where('id', '=', $paymentInfo['payment_id'])
                          ->find();

                        Db::name('payment_interface')
                          ->where('id', '=', $paymentInfo['payment_id'])
                          ->update([
                            'total_profit' => bcadd($interface['total_profit'], $profit, 2),
                            'cash_profit' => bcsub($interface['cash_profit'], $profit, 2),
                          ]);
                    }
                    Db::commit();
                } catch (Exception $e) {
                    Db::rollback();
                    Log::error('[异步通知] 订单事务处理异常=》' . $e->getMessage() . '|' . $e->getTraceAsString());
                    return $this->setError(false, '代付系统异常', 1);
                }
                Log::debug('[异步通知] 订单事务处理成功 ');
            }
            // 商户异步通知
            $is_send_notify = 1;
            if ($is_send_notify == 1 && $orderInfo['notify_status'] != 'yes') {
                //发送通知
                $result = $this->sendMerchantNotify($order_no);
                if ($result) {
                    return $this->setError(true, '[异步通知] 代付订单处理成功1');
                } else {
                    return $this->setError(false, '[异步通知] 代付商户异步通知发送失败', 4);
                }
            } else {
                return $this->setError(true, '[异步通知] 代付订单处理成功2');
            }
        }
        return $this->setError(false, '[异步通知] 无效请求参数的', 0);
    }

    /**
     * 客户短信通知
     * @param $mch_id
     * @return array
     * @throws \think\exception\DbException
     */
    public static function merchantSmsNotify($mch_id)
    {
        $merchantInfo = Merchant::get($mch_id);
        if ($merchantInfo) {
            $option['name'] = $merchantInfo['username'];
            $option['amount'] = '10000';
            $response = ::sendSms($merchantInfo['phone'], $option, 'SMS_139233657');
            if (strtolower($response->Code) == 'OK') {
                return [
                  'err_code' => 0,
                  'err_msg' => '客户短信通知发送成功'
                ];
            } else {
                return [
                  'err_code' => -1,
                  'err_msg' => '客户短信通知发送失败(' . $response->Message . ')'
                ];
            }
        } else {
            return [
              'err_code' => -1,
              'err_msg' => '未知的客户信息'
            ];
        }
    }

    /**
     * 客户异步成功发送通知
     * type = 1 支付自动回调结果
     *        2 手动发送回调 这个针对自动查询订单修改交易状态
     * @param $order_no
     * @param int $type
     * @return bool|mixed
     * @throws \think\exception\DbException
     */
    public function sendMerchantNotify($order_no, $type = 1)
    {
        $order = InterfaceOrder::where('order_no', '=', $order_no)->find();
        $mchInfo = Merchant::get($order->mch_id);
        if (empty($order->notify_url)) {
            return true;
        }

        $send_data = [
          'order_no' => (string)$order->order_no,
          'mch_order_no' => (string)$order->mch_order_no,
          'goods' => (string)$order->goods,
          'price' => (float)$order->price,
          'total_fee' => (float)$order->total_fee,
          'service_charge' => (float)$order->service_charge,
          'status' => (int)$order->status,
          'create_time' => date('Y-m-d H:i:s', $order->create_time),
          'pay_time' => date('Y-m-d H:i:s', $order->pay_time),
        ];

        $sign = $this->sign($send_data, $mchInfo);
        $send_data['sign'] = $sign;

        //循环url编码
        foreach ($send_data as &$item) {
            $item = urlencode($item);
        }
        unset($item);

        Log::debug('[异步通知]-[JOBS] 商户异步通知数据 (' . $order->notify_url . ')' . json_encode($send_data));
        $result = curl_post($order->notify_url, $send_data);
        Log::debug('[异步通知]-[JOBS] 商户异步通知返回结果 ' . $result);
        if (strtolower($result) == 'success') {
            $order->save(['notify_status' => 'yes']);
            return true;
        } else {
            if ($type == 1) {
                $order->save(['notify_status' => 'fail']);
            }
            return $this->setError(false, $result);
        }
    }

    /**
     * MD5签名
     * @param $data
     * @param $mchInfo
     * @return string
     */
    public function sign($data, $mchInfo)
    {
        ksort($data);
        $params_str = urldecode(http_build_query($data));
        $params_str = $params_str . '&key=' . $mchInfo->key;
        return md5($params_str);
    }

    /**
     * 查询接口
     * @param $option
     * @return mixed
     */
    public function orderQuery($option, $version = 1)
    {
        // 参数验证
        Log::debug('[2-0] 代付订单查询参数： ' . json_encode($option));

        // 参数过滤验证
        $vail_rule = [
          'mch_id' => 'require',
          'mch_order_no' => 'require',
        ];
        $vail_field = [
          'mch_id' => '商户号',
          'mch_order_no' => '商户订单号',
        ];

        $validate = new Validate($vail_rule,[],$vail_field);
        if (!$validate->check($option)) {
            return $this->setError(false, $validate->getError());
        }

        $orderInfo = InterfaceOrder::where([
          'mch_id'=>$option['mch_id'],
          'mch_order_no'=>$option['mch_order_no']
        ])->find();
        if(empty($orderInfo)){
            return $this->setError(false, '商户订单号不存在');
        }

        $resData['order_no'] = $orderInfo['order_no'];
        $resData['merchant_number'] = $orderInfo['merchant_number'];
        $resData['price'] = $orderInfo['price'];
        $resData['service_charge'] = $orderInfo['service_charge'];
        $resData['total_fee'] = $orderInfo['total_fee'];
        // 已经完成或者失败的订单直接返回订单结果 -1 代付失败  0未代付  1处理中  2已代付
//        if ($orderInfo['status'] != 1) {
//            $resData['status'] = $orderInfo['status'];
//            $resData['msg'] = $orderInfo['remark'];
//            Log::debug('[2-1] 代付订单查询结果： ' . json_encode($resData));
//            return $resData;
//        }

        $param['order_no'] = $orderInfo['order_no'];
        $param['merchant_number'] = $orderInfo['merchant_number'];
        $config = config('new_daifu_config');
        $daiFu = new Daifu();
        $daiFu->setConfig($config);
        $res = $daiFu->orderQuery($param);
        Log::debug('[2-2] 代付订单查询结果： ' . json_encode($res));
        $resData['msg'] = $res['op_err_msg'];
        $resData['status'] = $res['status'];
        $resData['amount'] = $res['amount']??'0.00';
        $resData['balance'] = $res['balance']??'0.00';
        return $resData;
    }

    /**
     * 定时任务订单任务查询
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function orderQueryJobs()
    {
        // 所有正在处理中的订单
        Log::debug('[JOBS] 定时任务订单任务查询...');
        $processOrder = InterfaceOrder::where(['status' => 1, 'type' => 1])
          ->order('id', 'asc')
          ->limit(6)
          ->select();
        Log::debug('[JOBS] 订单信息 '.json_encode($processOrder));
        if (empty($processOrder)) {
            Log::debug('no orders to update');
            return false;
        }
        if (is_array($processOrder) && !empty($processOrder)) {
            foreach ($processOrder as $order) {
                $param['mch_id'] = $order['mch_id'];
                $param['mch_order_no'] = $order['mch_order_no'];
                $param['merchant_number'] = $order['merchant_number'];

                $queryResult = $this->orderQuery($param);
                Log::debug('[JOBS] 订单查询返回结果 '.json_encode($queryResult));
                if ($queryResult['status'] == 1) {
                    Log::debug($queryResult['order_no'].' order is processing ');
                    continue;
                } else {
                    Log::debug('[JOBS] 订单状态为 '.$queryResult['status']);
                    $this->queryJobsHandle($queryResult);
                }
            }
        }
    }

    /**
     * Jobs 订单处理
     * @param array $option
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function queryJobsHandle($option)
    {
        $orderInfo = InterfaceOrder::where('order_no', '=', $option['order_no'])
          ->lock(true)
          ->find();
        Log::debug('[JOBS] 订单查询结果 '.json_encode($orderInfo));
        $paymentInfo = MerchantPaymentInterface::alias('a')
          ->field(['c.fee', 'a.payment_fee', 'a.id', 'a.payment_interface_id', 'c.id payment_id'])
          ->join('payment_interface c', 'a.payment_interface_id = c.id')
          ->where([
            'a.mch_id' => $orderInfo['mch_id'],
            'a.payment_interface_id' => $orderInfo['payment_interface_id']
          ])
          ->lock(true)->find();

        //-------------------------------开始处理订单---------------------------------------------------------------------
        $order_no = $option['order_no'];
        // 如果订单状态
        Log::debug('[JOBS] 开始处理订单 '.json_encode($option));
        if ($orderInfo['status'] == 1) {
            $updateData['id'] = $orderInfo->id;
            $updateData['pay_time'] = time();
            $updateData['remark'] = $option['msg'];
            if ($option['status'] == 2) {
                $updateData['status'] = 2;
                $updateData['remark'] = '代付成功，主动查询';
            } elseif ($option['status'] == -1) {
                $updateData['status'] = -1;
            }

            try {
                Db::startTrans();
                // 代付商户关系 加锁机制
                $merchantPayment = Db::name('merchant_payment_interface')->where([
                  'mch_id' => $orderInfo['mch_id'],
                  'payment_interface_id' => $paymentInfo['payment_interface_id']
                ])->lock(true)->find();

                // 1、修改订单状态
                Db::name('interface_order')->update($updateData);
                // 利润 = 客户代付手续费 - 商户代付手续费 eg: 5-2 = 3
                $profit = round(bcsub($merchantPayment['payment_fee'], $paymentInfo['fee'], 3), 2);

                Log::debug('[JOBS] 利润'.$profit);
                // 交易成功 00 ，未代付成功则返回客户储备金余额
                if ($option['status'] == -1) {
                    Log::error('[JOBS] 代付失败');
                    // 总代付费用 =  总代付金额 + 代付手续费，代付失败主动查询的结果是 $option['amount'] = null
                    $totalAmount = $orderInfo['total_fee'];
                    $merchantTotalFee = bcadd($merchantPayment['payment_reserve'], $totalAmount, 2);
                    Log::debug('[JOBS] 总代付费用 '.$totalAmount);
                    Log::debug('[JOBS] 总代备付金 '.$merchantTotalFee);
                    // 储备金增加
                    Db::name('merchant_payment_interface')
                      ->where('id', '=', $merchantPayment['id'])
                      ->setInc('payment_reserve', $totalAmount);

                    // 代付中利润减少
                    Db::name('payment_interface')
                      ->where('id', '=', $paymentInfo['payment_id'])
                      ->setDec('cash_profit', $profit);

                    // 总利润减少
                    Db::name('payment_interface')
                      ->where('id', '=', $paymentInfo['payment_id'])
                      ->setDec('all_profit', $profit);

                    // 总代付商户备付金增加
                    Db::name('payment_interface')
                      ->where('id', '=', $merchantPayment['payment_interface_id'])
                      ->setInc('total_payment_reserve', $totalAmount);

                    // 流水数据
                    Db::name('payment_interface_balance_record')->insert([
                      'mch_id' => $orderInfo['mch_id'],
                      'order_no' => $order_no,
                      'payment_interface_id' => $paymentInfo['payment_interface_id'],
                      'trade_merchant' => $orderInfo['mch_id'],
                      'trade_adversary' => json_encode($option),
                      'trade_channel' => json_encode($paymentInfo),
                      'record_type' => 1,
                      'type' => 1,
                      'money' => $totalAmount,
                      'before_money' => $merchantPayment['payment_reserve'],
                      'after_money' => $merchantTotalFee,
                      'remark' => '商户-' . $orderInfo['mch_id'] . '|入账' . $totalAmount . '元',
                      'created_at' => time()
                    ]);
                } elseif ($option['status'] == 2) {
                    Log::debug('[JOBS] 代付成功');
                    // 代付中利润添加到总利润中
                    $interface = Db::name('payment_interface')
                      ->where('id', '=', $paymentInfo['payment_id'])
                      ->find();

                    Db::name('payment_interface')
                      ->where('id', '=', $paymentInfo['payment_id'])
                      ->update([
                        'total_profit' => bcadd($interface['total_profit'], $profit, 2),
                        'cash_profit' => bcsub($interface['cash_profit'], $profit, 2),
                      ]);
                }
                Log::debug('[JOBS] 订单事务处理成功 ');
                Db::commit();
            } catch (Exception $e) {
                Db::rollback();
                Log::error('[JOBS] 系统异常=》' . $e->getMessage() . '|' . $e->getTraceAsString());
            }
            // 商户异步通知
            Log::debug("[JOBS] 商户异步通知状态 ".$orderInfo['notify_status']);
            if ($orderInfo['notify_status'] != 'yes') {
                //发送通知
                $result = $this->sendMerchantNotify($order_no);
                if ($result) {
                    Log::debug("[JOBS] 代付订单处理成功");
                } else {
                    Log::error("[JOBS] 代付商户异步通知发送失败 ".json_encode($result));
                }
            }
        }
        Log::debug("[JOBS] 代付订单处理结束");
    }
}