<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/7/11 9:36
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 商户代付模式
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\common\repositories;


use app\common\library\daifu\Daifu;
use app\common\library\daifu\DaiFuNew;
use app\common\library\DesJava;
use app\common\library\Dysms;
use app\common\model\InterfaceOrder;
use app\common\model\Merchant;
use app\common\model\MerchantPaymentInterface;
use app\common\repositories\abstracts\Repository;
use think\Db;
use think\Exception;
use think\Log;
use think\Validate;

class PaymentInterfaceRepository extends Repository
{
    const LOCK_EXPIRE = 86400; // 一天
    const ORDER_LOCK_EXPIRE = 60; // 1m

    public function model()
    {
        // TODO: Implement model() method.
        return false;
    }

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
     * 锁名
     * @param $mch_id
     * @param $card_no
     * @return string
     */
    private static function getLock($mch_id,$card_no)
    {
        return 'AGENT_LOCK:'.$mch_id.':'.$card_no;
    }

    /**
     * 订单锁
     * @param $mch_id
     * @param $order_no
     * @return string
     */
    private static function getOrderLock($mch_id,$order_no)
    {
        return 'AGENT_ORDER_LOCK:'.$mch_id.':'.$order_no;
    }
    
    /**
     * 新代付
     * @param int $mch_id
     * @param $option
     * @param $version
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function payNew($mch_id = 12001, $option, $version)
    {
        // 代付晚上10点到1点不能交易
        if (!static::tradeTime()) {
            return $this->setError(false, "每日 01:00 ~ 22:00 才可以可以进行代付交易");
        }

        Log::debug('【新】代付参数' . json_encode($option));
        // 参数过滤验证
        $vail_rule = [
            'account_id' => 'require', // @add Tinywan
            'total_fee' => 'require|between:1,50000',
            'order_sn' => 'require',
            'goods' => 'require',
            'username' => 'require|chsAlpha',
            'card_no' => 'require|number',
            'bank' => 'require|chsAlpha',
            'notify_url' => 'require',
        ];
        $vail_field = [
            'account_id' => '账户号', // @add Tinywan
            'total_fee' => '代付金额',
            'order_sn' => '商家订单号',
            'goods' => '商品名',
            'username' => '收款人账户名',
            'card_no' => '收款人账户号',
            'bank' => '收款银行名称',
            'notify_url' => '异步通知地址',
        ];
        $message = [
            'account_id.require'=>'账户号不能为空', // @add Tinywan
            'total_fee.require'=>'代付金额不能为空',
            'order_sn.require'=>'商家订单号不能为空',
            'goods.require'=>'商品名不能为空',
            'username.require'=>'收款人账户名不能为空',
            'username.chsAlpha'=>'收款人姓名只能是汉字、字母',
            'card_no.require'=>'收款人银行卡号不能为空',
            'card_no.number'=>'收款人银行卡号必须是数字',
            'bank.require'=>'收款银行名称不能为空',
            'bank.chsAlpha'=>'收款银行名称只能是汉字、字母',
        ];
        $validate = new Validate($vail_rule,$message,$vail_field);
        if (!$validate->check($option)) {
            return $this->setError(false, $validate->getError());
        }

        // 代付金额
        if ($option['total_fee'] < 1) {
            return $this->setError(false, "金额不能小于1.00元");
        }

        $paymentInfo = MerchantPaymentInterface::alias('a')
            ->field(['a.payment_fee', 'a.payment_interface_id', 'a.payment_reserve', 'a.max_amount', 'c.*'])
            ->join('payment_interface c', 'a.payment_interface_id = c.id')
            ->where(['a.mch_id' => $mch_id, 'a.payment_interface_id'=>$option['account_id'],'a.status' => 1])
            ->find();
        Log::debug('【新】代付商户关系信息 ' . json_encode($paymentInfo));

        if (empty($paymentInfo)) {
            return $this->setError(false, "代付通道没开启或者被禁用");
        }

        if ($paymentInfo['status'] != 1) {
            return $this->setError(false, "代付商户状态不可用");
        }

        // 单笔风控检测
        if ($option['total_fee'] > $paymentInfo['max_amount']) {
            return $this->setError(false, "单笔交易金额不能超过" . $paymentInfo['max_amount']);
        }

        Log::debug('【新】代付金额 = ' . $option['total_fee']);
        // 总代付金额 = 代付金额 + 代付手续费（5元）
        $totalAmount = $option['total_fee'] + $paymentInfo['payment_fee'];

        // 代付储备金余额检测
        if (round($totalAmount, 2) > round($paymentInfo['payment_reserve'], 2)) {
            $resData = [
                'balance'=>$paymentInfo['payment_reserve'], // 余额
                'fee'=>$paymentInfo['payment_fee']  // 手续费
            ];
            return $this->setError(false, "备付金余额不足，请联系管理员充值",0,$resData);
        }

        $order_no = 'D' . $mch_id . date('ymdHis', time()) . rand(1000, 9999);
        /**
         * 对每个业务订单生成预支付订单时，检查业务订单支付已支付
         * 原因：
         * 1、网络延迟
         * 2、代付平台接口响应异常原因
         */
        $prepaidOrder = [
            'order_no'=>$order_no,
            'merchant_number'=>$paymentInfo['merchant_number'],
        ];
        if($this->prepaidOrderQuery($prepaidOrder)){
            return $this->setError(false,'该订单已经代付或者正在代付中，请不要重复订单', -1);
        }

        // 订单重复提交
        try{
            $redis = redis_connect();
            $lock = self::getLock($mch_id,$option['card_no']);
            if($redis->get($lock)){
                return $this->setError(false, "该卡号正在在代付，请不要重复提交");
            }
            $redis->set($lock,1,self::LOCK_EXPIRE);
        }catch (Exception $e){
            Log::error('【新】Redis 加锁失败' . $e->getMessage() . '|' . $e->getTraceAsString());
            return $this->setError(false, '代付系统异常，请稍后再试', 100);
        }

        // 代付商户信息
        $data['merchant_number'] = $paymentInfo['merchant_number'];
        $data['wallet_id'] = $paymentInfo['wallet_id'];
        $data['asset_id'] = $paymentInfo['asset_id'];
        $data['password_type'] = $paymentInfo['password_type'];
        $data['pay_password'] = $paymentInfo['pay_password'];
        $data['encrypt_type'] = $paymentInfo['encrypt_type'];
        $data['currency'] = $paymentInfo['currency'];
        $data['customer_type'] = $paymentInfo['customer_type'];
        $data['asset_type_code'] = $paymentInfo['asset_type_code'];
        $data['account_type_code'] = $paymentInfo['account_type_code'];

        // 客户信息
        $data['order_no'] = $order_no;
        $data['username'] = $option['username'];
        $data['card_no'] = $option['card_no'];
        $data['amount'] = $option['total_fee'];
        $data['bank_name'] = $option['bank'];
        $data['notify'] = config('server_url') . '/paymentInterfaceNotify';
        $data['attr_type'] = '01';

        // 配置商户aid和key
        $config = [
            'aid'=>$paymentInfo['aid'],
            'key'=>$paymentInfo['app_key']
        ];
        Log::debug('【新】配置商户aid和key ' . json_encode($config));
        $daiFu = new Daifu();
        $daiFu->setConfig($config);
        $daiFuResult = $daiFu->newPaying($data);
        Log::debug('【新】代付请求结果' . json_encode($daiFuResult));

        // 验证检测
        if(isset($daiFuResult['err_code']) && $daiFuResult['err_code'] == -1){
            Log::error('【新】 请求数据验证失败 ' . json_encode($daiFuResult));
            return $this->setError(false, $daiFuResult['err_msg'], -1);
        }
        // 接口请求不成功
        if ($daiFuResult['status'] == 'fail') {
            Log::error('【新】 美付宝代付接口请求失败 ' . json_encode($daiFuResult));
            // 创建一个预代付订单表记录
            $prepaid_data = [
                'mch_id' => $mch_id,
                'payment_interface_id' => $paymentInfo['payment_interface_id'],
                'status' => 0,
                'order_no' => $order_no,
                'mch_order_no' => $option['order_sn'],
                'channel_order_no' => $daiFuResult['order_id'],
                'merchant_number' => $paymentInfo['merchant_number'],
                'channel_return_data' => json_encode($daiFuResult),
                'goods' => $option['goods'],
                'price' => $option['total_fee'],
                'customer_name' => $option['username'],
                'account_number' => $option['card_no'],
                'type' => 3,
                'create_time' => time(),
            ];
            Log::error('【新】创建一个预代付订单表记录 ' . json_encode($prepaid_data));
            Db::name('interface_order')->insert($prepaid_data);
            return $this->setError(false, $daiFuResult['err_msg'], -1);
        }

        //开始业务处理
        Db::startTrans();
        try {
            // 加锁机制
            $merchantPayment = Db::name('merchant_payment_interface')->where([
                'mch_id' => $mch_id,
                'payment_interface_id' => $paymentInfo['payment_interface_id']
            ])->lock(true)->find();

            // 利润 = 商户手续费 - 代理手续费
            $net_profit = bcsub($merchantPayment['payment_fee'], $paymentInfo['fee'], 3);
            // 子商户提现则手续费是0
            // 创建订单
            $insert_data = [
                'mch_id' => $mch_id,
                'payment_interface_id' => $merchantPayment['payment_interface_id'],
                'status' => 1,
                'order_no' => $order_no,
                'mch_order_no' => $option['order_sn'],
                'channel_order_no' => $daiFuResult['order_id'],
                'merchant_number' => $paymentInfo['merchant_number'],
                'channel_return_data' => json_encode($daiFuResult),
                'goods' => $option['goods'],
                'price' => $option['total_fee'],
                'net_profit' => $net_profit,
                'customer_name' => $option['username'],
                'account_number' => $option['card_no'],
                'total_fee' => $option['total_fee'] + $merchantPayment['payment_fee'],
                'service_charge' => $merchantPayment['payment_fee'],
                'type' => 1,
                'notify_url' => $option['notify_url'],
                'return_url' => $data['notify'],
                'create_time' => time(),
            ];
            // 2、创建订单
            Log::debug('【新】代付创建订单 ' . json_encode($insert_data));
            Db::name('interface_order')->insert($insert_data);

            // 3、总代付费用 =  代付金额 + 代付手续费
            $totalAmount = bcadd($option['total_fee'], $paymentInfo['payment_fee'], 2);
            Log::debug('【新】总手续费 ' . $totalAmount);

            // 4、剩余备付金 = 总备付金 - 总代付费用 1000 - 20 + 5 = 75
            $balancePaymentReserve = bcsub($paymentInfo['payment_reserve'], $totalAmount, 2);
            Log::debug('【新】剩余商户储备金 ' . $balancePaymentReserve);

            // 5、扣除代付商户储备金
            Db::name('merchant_payment_interface')->where([
              'mch_id' => $mch_id,
              'payment_interface_id' => $merchantPayment['payment_interface_id']
            ])->setDec('payment_reserve',$totalAmount);

            // 利润 = 客户代付手续费 - 商户代付手续费 eg: 5-2 = 3
            $profit = round(bcsub($merchantPayment['payment_fee'], $paymentInfo['fee'], 3), 2);
            Log::debug('【新】总利润增加 ' . $profit);

            // 6-1、增加提现中利润
            Db::name('payment_interface')
                ->where('id', '=', $merchantPayment['payment_interface_id'])
                ->setInc('cash_profit', $profit);

            // 6-2、总利润增加
            Db::name('payment_interface')
                ->where('id', '=', $merchantPayment['payment_interface_id'])
                ->setInc('all_profit', $profit);

            // 6-3、减少总备付金
            Db::name('payment_interface')
                ->where('id', '=', $merchantPayment['payment_interface_id'])
                ->setDec('total_payment_reserve', $totalAmount);

            // 7、账户均衡表
            Db::name('payment_interface_balance_record')->insert([
                'mch_id' => $mch_id,
                'order_no' => $order_no,
                'payment_interface_id' => $paymentInfo['payment_interface_id'],
                'trade_merchant' => $mch_id,
                'trade_adversary' => json_encode($option),
                'trade_channel' => json_encode($paymentInfo),
                'record_type' => 1,
                'type' => 2,
                'money' => $totalAmount,
                'before_money' => $paymentInfo['payment_reserve'],
                'after_money' => $balancePaymentReserve,
                'remark' => '商户-' . $mch_id . '|出账-' . $totalAmount . '元',
                'created_at' => time()
            ]);
            Db::commit();
            Log::debug('【新】支付事务结束 ');
        } catch (Exception $e) {
            Db::rollback();
            Log::error('【新】代付请求异常' . $e->getMessage() . '|' . $e->getTraceAsString());
            return $this->setError(false, '代付系统异常，请稍后再试', 500);
        }
        $req_data = [
            'status' => 1, // 1 正在处理中 2 完成
            'order_no' => $insert_data['order_no'],
            'mch_order_no' => $insert_data['mch_order_no'],
            'price' => $insert_data['price'],
            'service_charge' => $insert_data['service_charge'],
            'total_fee' => $insert_data['total_fee'],
        ];
        return $req_data;
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
            return $this->setError(false, ' 非异步通知费POST 请求', 0);
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
                return $this->setError(false, $order_no . '订单号不存在', 0);
            }

            if ($postData['orderid'] == null || $postData['orderid'] == '') {
                return $this->setError(false, '非法的系统订单', 1);
            }

            $orderInfo = InterfaceOrder::where('order_no', '=', $order_no)
              ->lock(true)
              ->find();
            if (!$orderInfo) {
                return $this->setError(false,  '系统订单不存在', 0);
            }
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
                // 订单加锁
                try{
                    $redis = redis_connect();
                    $orderLock = self::getOrderLock($orderInfo['mch_id'],$orderInfo['order_no']);
                    if($redis->get($orderLock)){
                        Log::debug('[JOBS] 获取锁失败，订单号 '.$order_no);
                        return $this->setError(false, '获取锁失败，正在处理中', 0);
                    }else{
                        Log::debug('[JOBS] 获取锁成功，订单号 '.$order_no);
                        $redis->set($orderLock,1,self::ORDER_LOCK_EXPIRE);
                    }
                }catch (Exception $e){
                    Log::error('[JOBS] Redis 加锁失败' . $e->getMessage() . '|' . $e->getTraceAsString());
                    return $this->setError(false, $order_no . ' 获取锁异常', 0);
                }

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
            // 释放锁
            $redis = redis_connect();
            $lock = self::getLock($orderInfo['mch_id'],$orderInfo['account_number']);
            $unlock = $redis->del($lock);
            Log::debug('[异步通知] 释放卡号锁结果：'.$unlock);
            $orderLock = self::getOrderLock($orderInfo['mch_id'],$orderInfo['order_no']);
            $unOrderLock = $redis->del($orderLock);
            Log::debug('[异步通知] 释放订单锁结果：'.$unOrderLock);
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
            $response = Dysms::sendSms($merchantInfo['phone'], $option, config('aliyun')['sms']['agentpay_template']);
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
     * @param int $version
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
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
            // 订单加锁
            try{
                $redis = redis_connect();
                $orderLock = self::getOrderLock($orderInfo['mch_id'],$orderInfo['order_no']);
                if($redis->get($orderLock)){
                    Log::debug('[JOBS] 获取锁失败，订单号 '.$order_no);
                    return false;
                }else{
                    Log::debug('[JOBS] 获取锁成功，订单号 '.$order_no);
                    $redis->set($orderLock,1,self::LOCK_EXPIRE);
                }
            }catch (Exception $e){
                Log::error('[JOBS] Redis 加锁失败' . $e->getMessage() . '|' . $e->getTraceAsString());
                return false;
            }

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
            // 释放锁
            $redis = redis_connect();
            $lock = self::getLock($orderInfo['mch_id'],$orderInfo['account_number']);
            $unlock = $redis->del($lock);
            Log::debug('[JOBS] 释放卡号锁结果：'.$unlock);
            $orderLock = self::getOrderLock($orderInfo['mch_id'],$orderInfo['order_no']);
            $unOrderLock = $redis->del($orderLock);
            Log::debug('[JOBS] 释放订单锁结果：'.$unOrderLock);
            
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

    /**
     * 预代付订单查询
     * @param $orderInfo
     * @return bool
     */
    protected function prepaidOrderQuery($orderInfo)
    {
        $param['order_no'] = $orderInfo['order_no'];
        $param['merchant_number'] = $orderInfo['merchant_number'];
        $config = config('new_daifu_config');
        $daiFu = new Daifu();
        $daiFu->setConfig($config);
        $res = $daiFu->orderQuery($param);
        Log::debug('【新】预订单查询结果: ' . json_encode($res));
        if($res['status'] != -1){
            Log::debug('【新】预代付订单: ' . $param['order_no'].'【错误信息】'.$res['op_err_msg']);
            return true;
        }
        return false;
    }
}