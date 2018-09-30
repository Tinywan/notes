<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/9/30 17:50
 * |  Mail: 756684177@qq.com
 * |  Desc: 渠道支付路由类
 * '------------------------------------------------------------------------------------------------------------------*/
namespace app\common\repositories;

use app\common\repositories\contracts\PayRepositoryAbstract;

class ChannelPayRepository extends PayRepositoryAbstract
{

    /**
     * @var array 商户信息
     */
    protected $mchInfo = [];

    /**
     * @var array 支付渠道
     */
    protected $channel = [];

    /**
     * @var array 渠道配置
     */
    protected $channelConfig = [];

    /**
     * @var array 支付方式配置
     */
    protected $parmentConfig = [];

    /**
     * @var array 商户的支付方式配置
     */
    protected $mchPayment = [];

    /**
     * 支付方式对应方法
     */
    protected $parment = [];

    /**
     * @var array 渠道对应的class
     */
    protected $channelClass = [];

    /**
     * @var array 系统配置
     */
    protected $systemConfig = [];


    function __construct()
    {
        parent::__construct();

        $this->parment = config('parment_method');
        $this->channelClass = config('channel_class');
        $this->systemConfig = get_system_config();
    }

    public function model()
    {
        return false;
    }

    /**
     * 支付请求
     * @param $mch_id
     * @param $pay_ment
     * @param array $option
     * @return array|mixed
     * @throws \Exception
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function pay($mch_id, $pay_ment, $option = [], $version)
    {
        //查询商户信息
        $this->mchInfo = Merchant::get($mch_id);
        //查询支付方式信息
        if (empty($this->parment[$pay_ment])) {
            return $this->setError(false, '支付方式不存在');
        }
        $this->parmentConfig = PayPayment::get(['key' => $pay_ment]);
        if (empty($this->parmentConfig)) {
            return $this->setError(false, '支付方式不存在');
        }
        //查询系统支付方式总开关
        if ($this->parmentConfig->status == 0) {
            return $this->setError(false, '支付方式未开启');
        }
        //查询商户的支付方式配置
        $this->mchPayment = MerchantPayment::get(['merchant_id' => $mch_id, 'payment' => $this->parmentConfig->id]);
        if (empty($this->mchPayment) || $this->mchPayment->status == 0 || empty($this->mchPayment->channel_ids)) {
            return $this->setError(false, '支付方式未启用');
        }

        //查询渠道配置
        if ($this->mchPayment->type == 1) {
            //单渠道模式
            //查询支付方式所选用的渠道
            $this->channel = PayChannel::get($this->mchPayment->channel_ids);

        } elseif ($this->mchPayment->type == 2) {
            //多渠道轮询模式
            return $this->setError(false, '系统暂不支持轮询渠道模式');
        }
        if (empty($this->channel)) {
            return $this->setError(false, '平台方错误，未知渠道！');
        }
        if ($this->channel->status == 0) {
            return $this->setError(false, '平台方错误，渠道支付方式已关闭！');
        }

        //查询渠道配置
        $this->channelConfig = PayChannelConfig::get($this->channel->channel_config_id);
        if (empty($this->channelConfig)) {
            return $this->setError(false, '平台方错误，渠道配置不存在！');
        }
        if (empty($this->channelConfig->status)) {
            return $this->setError(false, '平台方错误，渠道已关闭！');
        }
        if (empty($this->channelClass[$this->channelConfig->class_namespace])) {
            return $this->setError(false, '平台方错误，渠道标识错误！');
        }
        //如果是网关支付并且属于下属渠道，则需要取对应渠道的银行编码
        if ($pay_ment == 'gateWay' && in_array($this->channelConfig->class_namespace, [
                'xtenpay', // 杉德网关
                'xtenpay_quick', // 杉德快捷
                'allscore', // 商银信
                'saas', // 和壹付
                'niuyongpay', // 牛通支付
            ])) {
            $bank_info = BankCodeChannelGatway::get(['b_id' => $option['bank_code'], 'channel' => $this->channelConfig->class_namespace]);
            if (!$bank_info) {
                return $this->setError(false, '银行编码错误！');
            } else {
                $option['bank_code'] = $bank_info->bank_code;
            }
        }

        //检测昨日冻结金额是否已经转移
        $mch_account_info = MerchantAccount::get(['mch_id' => $mch_id, 'channel' => $this->channelConfig->class_namespace]);
        if ($this->channelConfig->class_namespace != 'ips') {
            if (empty($mch_account_info) || $mch_account_info->withdraw_cash_time < strtotime(date('Y-m-d'))) {
                $jobs = new JobsRepository();
                $jobs->qingsuan(); //如果自动执行失败了，就主动再去执行转移
                Log::error('昨日冻结金额未转移=》' . json_encode($mch_account_info->toArray()));
            }
        }

        //如果是快捷支付，为用户唯一ID参数加上商户标识，防止重复
        if ($pay_ment == 'unQuickpay' && !empty($option['user_id'])) {
            $option['user_id'] = substr($mch_id, -4) . $option['user_id'];
        }
        if (empty($option['notify_url'])) {
            return $this->setError(false, '异步通知地址不能为空！');
        }
        if (empty($option['return_url'])) {
            return $this->setError(false, '同步跳转地址不能为空！');
        }

        //实例化渠道类
        $object = App::invokeClass($this->channelClass[$this->channelConfig->class_namespace]);
        Log::debug('[4]' . json_encode($object));
        //设置商户渠道配置
        $_result = $object->setMchChannelConfig($mch_id);
        if (!$_result) {
            $error = $object->getReturnMsg();
            return $this->setError(false, $error['message']);
        }
        Log::debug('[5]' . json_encode($object));
        //============== 2018-07-11 增加结算方式判断
        $channel_merchant_clear = Db::name('channel_merchant_clear')
            ->where([
                'channel' => $_result->channel,
                'channel_mch_id' => $_result->channel_mch_id,
                'payment' => $pay_ment
            ])->find();
        Log::debug('[6]' . json_encode($channel_merchant_clear));
        if (empty($channel_merchant_clear)) {
            return $this->setError(false, '商户号未配置结算方式');
        }

        //============ 2018-6-26 11:05 新增预扣费判断 =============//
        if ($this->channelConfig->class_namespace == 'chilong_alipay'
            && $pay_ment == 'aliF2f'
            && $_result->extend_1 == 1
        ) {
            //如果是赤龙支付宝当面付，且结算模式为预扣费
            //手续费
            $service_charge = bcmul($this->mchPayment->rate, $option['total_fee'], 2);
            if ($service_charge < $this->channel->base_poundage) {
                //限制手续费下限
                $service_charge = (float)$this->channel->base_poundage;
            }

            $deducting_fee = bcmul($this->mchInfo->deducting_fee, 1, 2);
            if ($service_charge > $deducting_fee) {
                return $this->setError(false, '预扣费账户余额不足');
            }
        }
        Log::debug('[7]' . json_encode($object));
        // end
        //====== 风控判断 =======
        //检测是否配置有风控记录
        //检测单笔金额
        if ($option['total_fee'] > $this->mchPayment->single_pen_max_money) {
            return $this->setError(false, '单笔金额超出限制！');
        }
        //检测单日总计金额
        $sum_total = Order::where([
            'mch_id' => $mch_id,
            'channel' => $this->channelConfig->class_namespace,
            'payment' => $pay_ment,
            'channel_merchant_no' => $_result->channel_mch_id,
            'pay_time' => ['egt', strtotime(date('Y-m-d', time()))],
            'status' => ['egt', 1]
        ])->sum('total_fee');
        if (empty($sum_total)) {
            $sum_total = 0;
        }
        if ($sum_total >= $this->mchPayment->day_max_money) {
            return $this->setError(false, '今日支付额度已用尽！', -20001);
        }
        // end

        $fuc = $this->parment[$pay_ment];
        //验证订单号是否重复
//        $order = Order::get(['mch_order_no' => $option['order_sn'], 'mch_id' => $mch_id]);
//        if ($order){
//            return $this->setError(false, '订单号重复');
//        }

        //平台订单号
        $order_no = 'S' . $this->mchInfo->id . date('ymdHis', time()) . rand(1000, 9999);
        $option['order_no'] = $order_no;

        //发起支付请求
        $result = $object->$fuc($option);
        Log::debug('[9]' . json_encode($result));
        if (!$result) {
            $error = $object->getReturnMsg();
            Log::error('[99999]' . json_encode($error));
            Log::error('[11111111111]' . json_encode($error['message']));
            return $this->setError(false, $error['message'], $error['code'], $error['data']);
        }
        Log::debug('渠道接口整理后数据=》' . json_encode($result));
        if (empty($result['pay_url'])) {
            return $this->setError(false, '系统异常，请稍后再试！');
        }

        $order = $this->createOrder(
            $this->channelConfig->class_namespace, //渠道标识
            $this->parmentConfig->key, //支付方式标识
            $result, //接口请求返回
            $option, //业务请求参数
            $_result->channel_mch_id, //渠道商户号
            $this->channel->rate, //费率
            $channel_merchant_clear
        );
        if ($order) {
            if (isset($result['channel_order_no'])) {
                unset($result['channel_order_no']);
            }

            $result_data = array_merge($result, $order);
            if (!empty($result_data['pay_url'])) {
                $result_data['pay_url'] = config('server_url') . url('/pay', '', '') . '?order_no=' . $result_data['order_no'];
            }

//            return $result_data;
            $res = [
                'mch_order_no' => $result_data['mch_order_no'],
                'order_no' => $result_data['order_no'],
                'total_fee' => $result_data['total_fee'],
                'rate' => $result_data['rate'],
                'service_charge' => $result_data['service_charge'],
            ];
            if (isset($result_data['pay_url'])) {
                $res['pay_url'] = $result_data['pay_url'];
            }

            return $res;
        } else {
            return $this->setError(false, '平台方错误，订单创建异常！');
        }
    }

    /**
     * 创建订单
     * @param $channel
     * @param $payment
     * @param $data
     * @param $option
     * @param $channel_mch_id
     * @param $channel_rate
     * @param $channel_merchant_clear
     * @return array|bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function createOrder(
        $channel,
        $payment,
        $data,
        $option,
        $channel_mch_id,
        $channel_rate,
        $channel_merchant_clear
    )
    {
        $order_no = $option['order_no'];
        $mch_order_no = $option['order_sn'];
        $channel_order_no = $data['channel_order_no'];

        $channel_return_data = $data;
        if (is_array($channel_return_data)) {
            $channel_return_data = json_encode($channel_return_data);
        }

        $insert_data = [
            'mch_id' => $this->mchInfo->id,
            'order_no' => $order_no,
            'mch_order_no' => $mch_order_no,
            'channel_order_no' => $channel_order_no,
            'goods' => $option['goods'],
            'price' => $option['total_fee'],
            'total_fee' => $option['total_fee'],
            'channel' => $channel,
            'payment' => $payment,
            'channel_merchant_no' => $channel_mch_id,
            'cost_rate' => $channel_rate,
            'rate' => $this->mchPayment->rate,
            'pay_url' => $data['pay_url'],
            'notify_url' => $option['notify_url'],
            'return_url' => empty($option['return_url']) ? '' : $option['return_url'],
            'create_time' => time(),
            'channel_return_data' => $channel_return_data,
            'jiesuan_clear_type' => $channel_merchant_clear['clear_type'],
            'jiesuan_delay_time' => $channel_merchant_clear['delay_time']
        ];
        if (isset($data['qr_code'])) {
            $insert_data['qrcode'] = $data['qr_code'];
        }
        if (isset($data['pay_type'])) {
            $insert_data['pay_type'] = $data['pay_type'];
        }
        if (isset($option['client'])) {
            $insert_data['client'] = $option['client'];
        }

        //手续费
        $service_charge = bcmul($insert_data['rate'], $insert_data['total_fee'], 2);
        //成本手续费
        $cost_service_charge = bcmul($insert_data['cost_rate'], $insert_data['total_fee'], 4);

        if ($service_charge < $this->channel->base_poundage) {
            //限制手续费下限
            $service_charge = $this->channel->base_poundage;
            $cost_service_charge = $this->channel->base_poundage;
        }
//        elseif ($service_charge > $this->channel->poundage){
//            //限制手续费上限
//            $service_charge = $this->channel->poundage;
//        }
        if ($cost_service_charge < $this->channel->base_poundage) {
            $cost_service_charge = $this->channel->base_poundage;
            $net_profit = '0.0000';
            if ($service_charge > $this->channel->base_poundage) {
                $net_profit = bcsub($service_charge, $cost_service_charge, 4);
            }
        } else {
            $net_profit = bcsub($service_charge, $cost_service_charge, 4);
        }

        $insert_data['platform_income'] = $net_profit;

        // ==========================支付代理商=========================================================================
        $merchantInfo = Merchant::get($this->mchInfo->id);
        if (!empty($merchantInfo->agents_id)) {
            $payPaymentInfo = PayPayment::where('key', '=', $payment)->find();
            $channelConfigInfo = PayChannelConfig::where('class_namespace', '=', $channel)->find();

            $agentsPayment = AgentsChannelPayment::where([
                'agents_id' => $merchantInfo->agents_id,
                'payment_id' => $payPaymentInfo->id, // 6
                'channel_id' => $channelConfigInfo->id // 2
            ])->find();

            // 代理费率 jd_agents_payment
            $agents_rate = '0.0000';
            if ($agentsPayment) {
                $agents_rate = $agentsPayment->agents_rate;
            }

            // (交易金额 x 代理费率) 小数请和上面保持一致
            $agent_service_charge = bcmul($insert_data['total_fee'], $agents_rate, 2);
            // 代理收入 = 交易手续费 - (交易金额 x 代理费率)
            $agents_income = bcsub($service_charge, $agent_service_charge, 2);
            // 平台收入 = 交易手续费 - (交易金额 x 平台费率)  -  代理收入
            $tmp_charge = bcadd($cost_service_charge, $agents_income, 4);
            $platform_income = bcsub($service_charge, $tmp_charge, 4);
            // 下线
            if ($net_profit == '0.00') {
                $agents_income = '0.00';
                $platform_income = '0.00';
            }

            // @add 平台手续费下线大于代理商服务费1
            if ($cost_service_charge > $agent_service_charge) {
                $agents_income = '0.00';
                $platform_income = $net_profit;
            }

            $insert_data['agents_id'] = $merchantInfo->agents_id;
            $insert_data['salesman_id'] = $merchantInfo->salesman_id;
            $insert_data['agents_rate'] = $agents_rate;
            $insert_data['agents_income'] = $agents_income;
            $insert_data['platform_income'] = $platform_income;
        }
        // ==========================支付代理商=========================================================================

        $insert_data['service_charge'] = $service_charge;
        $insert_data['cost_service_charge'] = $cost_service_charge;
        $insert_data['net_profit'] = $net_profit;

        $result = Order::create($insert_data);
        if ($result) {
            $req_data = [
                'order_no' => $insert_data['order_no'],
                'mch_order_no' => $insert_data['mch_order_no'],
                'total_fee' => $insert_data['total_fee'],
                'rate' => $insert_data['rate'],
                'service_charge' => $insert_data['service_charge']
            ];

            return $req_data;
        } else {
            return false;
        }
    }

    /**
     * 异步通知
     * @return mixed|void
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function notifyOld()
    {
        $data = request()->param();
        if (empty($data)) {
            $xml_data = file_get_contents("php://input");
            $data = simplexml_load_string($xml_data);
            $data = json_decode(json_encode($data), true);
        }

        $channel_str = ''; //渠道标识
        $mch_order_no = ''; //商户订单号
        $mch_id = ''; //商户号

        Log::info('异步通知=》' . json_encode($data));

        //区分渠道
        if (isset($data['fl_order_no']) || isset($data['fl_proxy_no'])) {
            //摇钱树
            $channel_str = 'yaoqianshu';
            if (!empty($data['order_no'])) {
                $mch_order_no = $data['order_no'];
            } elseif (!empty($data['proxy_no'])) {
                $mch_order_no = $data['proxy_no'];
            }

        } elseif (isset($data['trade_no'])) {
            //杉德
            $mch_order_no = $data['out_trade_no'];
            //区分同一渠道开了多个渠道问题，取订单中的渠道标识
            $order = Order::get(['order_no' => $mch_order_no]);
            if (empty($order)) {
                return $this->setError(false, '订单不存在');
            }
            $channel_str = $order->channel;

        } elseif (isset($data['jnet_bill_no']) || (isset($data['agent_id']) && isset($data['encrypt_data']))
            || (isset($data['agent_id']) && isset($data['hy_bill_no']))
        ) {
            //汇付宝
            $channel_str = 'heepay';
            if (!empty($data['jnet_bill_no'])) {
                $mch_order_no = $data['agent_bill_id'];
                $order = Order::get(['order_no' => $mch_order_no]);
                if (empty($order)) {
                    return $this->setError(false, '订单不存在');
                }
                $channel_str = $order->channel;
            } elseif (!empty($data['agent_id']) && !empty($data['encrypt_data'])) {
                //汇付宝快捷支付，查询商户渠道配置
                $mch_channel_config = MerchantChannelConfig::alias('a')
                    ->where(['a.channel' => 'heepay', 'b.channel_mch_id' => $data['agent_id']])
                    ->join('channel_merchant b', 'a.channel_mch_id = b.id', 'left')
                    ->find();
                $mch_id = $mch_channel_config->mch_id;
            } elseif (!empty($data['hy_bill_no']) && !empty($data['agent_id'])) {
                //汇付宝批量付款
                $mch_order_no = $data['batch_no'];
                $order = MerchantWithdrawCash::get(['order_no' => $mch_order_no]);
                if (empty($order)) {
                    return $this->setError(false, '订单不存在');
                }
                $channel_str = $order->channel;
            }
        } elseif (isset($data['outOrderId'])) {
            //商银信
            $channel_str = 'allscore';
            $mch_order_no = $data['outOrderId'];
        } elseif (isset($data['paymentResult'])) {
            //环迅支付
            $channel_str = 'ips';
            $xmlResult = new SimpleXMLElement($data['paymentResult']);
            $mch_order_no = $xmlResult->GateWayRsp->body->MerBillNo;
        } elseif (isset($data['ipsResponse'])) {
            //环迅提现
            $channel_str = 'ips';
            $xmlResult = new SimpleXMLElement($data['ipsResponse']);
            //查询商户配置
            $config = ChannelMerchant::get(['channel' => $channel_str, 'channel_mch_id' => $xmlResult->argMerCode]);
            //解密数据
            $Crypt3Des = new IpsCrypt3Des($config->extend_2, $config->extend_3);
            $des_result = $Crypt3Des->decrypt($xmlResult->p3DesXmlPara);
            if (empty($des_result)) {
                Log::error('ips提现异步通知(数据解密失败)=>' . json_encode($xmlResult));
            }
            $p3DesXmlParaResult = new SimpleXMLElement($des_result);
            Log::info('环讯提现异步通知=》' . json_encode($p3DesXmlParaResult));
            $mch_order_no = $p3DesXmlParaResult->body->merBillNo;
        } elseif (isset($data['reCode']) && isset($data['result'])) {
            $channel_str = 'saas';
            if (isset($data['trxMerchantOrderno'])) {
                $mch_order_no = $data['trxMerchantOrderno'];
            } elseif (isset($data['merchantOrderNo'])) {
                $mch_order_no = $data['merchantOrderNo'];
            }
        } elseif (isset($data['seller_email']) && isset($data['buyer_id'])) {
            //赤龙支付宝
            $channel_str = 'chilong';
            $mch_order_no = $data['out_trade_no'];
        } elseif (isset($data['charset']) && isset($data['data'])) {
            //杉德总部
            $channel_str = 'sandpay';
            $de_data = json_decode($data['data'], true);
            $mch_order_no = $de_data['body']['orderCode'];
        }

        if (empty($channel_str)) {
            return $this->setError(false, '非法访问');
        }
        if (empty($mch_id)) {
            //如果没有商家ID，则通过订单去查询商家id
            $order = Order::get(['order_no' => $mch_order_no]);
            if (empty($order)) {
                $order = MerchantWithdrawCash::get(['order_no' => $mch_order_no]);
                if (empty($order)) {
                    return $this->setError(false, '订单不存在');
                }
            }
            $mch_id = $order->mch_id;
        }
        $object = App::invokeClass(config('channel_class')[$channel_str]);
        //设置商户渠道配置
        $_result = $object->setMchChannelConfig($mch_id);
        if (!$_result) {
            $error = $object->getReturnMsg();
            return $this->setError(false, $error['message']);
        }
        $result = $object->notify($data);
        if (!$result) {
            $error = $object->getReturnMsg();
            return $this->setError(false, $error['message'], $error['code']);
        }
        //先将字符串转大写，避免有些渠道自动转小写匹配不到订单问题
        $result['order_no'] = strtoupper($result['order_no']);

        //区分订单类型 SN_支付订单   CS_提现订单
        $order_no = $result['order_no'];
        $type = mb_substr($order_no, 0, 1, 'utf-8');

        if ($type == 'S') {
            //支付订单
            $order_info = Db::name('order')->where(['order_no' => $order_no])->find();
            if ($order_info['jiesuan_clear_type'] != 0) {
                $res = $this->payNotify($channel_str, $result);
            } else {
                $res = $this->payNotifyOld($channel_str, $result);
            }
        } elseif ($type == 'C') {
            //提现订单
            $res = $this->cashNotify($channel_str, $result);
        } else {
            $res = $this->setError(false, '未知的订单类型');
        }

        if (!$res) {
            return false;
        }

        return $object->notifySuccessEcho($mch_order_no);

    }

    /**
     * 异步通知(优化)
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function notify()
    {
        $data = request()->param();
        if (empty($data)) {
            $xml_data = file_get_contents("php://input");
            $data = simplexml_load_string($xml_data);
            $data = json_decode(json_encode($data), true);
        }

        $mch_id = ''; //商户号
        $mch_order_no = ''; //商户订单号
        $channel_merchant_id = ''; //渠道商户号

        Log::debug('异步通知=》' . json_encode($data));

        // ============================ 取异步通知关键信息 订单号或三方商户号 ================= //
        if (isset($data['fl_order_no']) || isset($data['fl_proxy_no'])) {
            //摇钱树
            if (!empty($data['order_no'])) {
                $mch_order_no = $data['order_no'];
            } elseif (!empty($data['proxy_no'])) {
                $mch_order_no = $data['proxy_no'];
            }
        } elseif (isset($data['trade_no'])) {
            //杉德
            $mch_order_no = $data['out_trade_no'];
        } elseif (isset($data['jnet_bill_no']) || (isset($data['agent_id']) && isset($data['encrypt_data']))
            || (isset($data['agent_id']) && isset($data['hy_bill_no']))
        ) {
            //汇付宝
            if (!empty($data['jnet_bill_no'])) {
                //支付回调
                $mch_order_no = $data['agent_bill_id'];
            } elseif (!empty($data['agent_id']) && !empty($data['encrypt_data'])) {
                //快捷回调
                $channel_merchant_id = $data['agent_id'];
            } elseif (!empty($data['hy_bill_no']) && !empty($data['agent_id'])) {
                //汇付宝批量付款
                $mch_order_no = $data['batch_no'];
            }
        } elseif (isset($data['outOrderId'])) {
            //商银信
            $mch_order_no = $data['outOrderId'];
        } elseif (isset($data['paymentResult'])) {
            //环迅支付
            $xmlResult = new SimpleXMLElement($data['paymentResult']);
            $mch_order_no = $xmlResult->GateWayRsp->body->MerBillNo;
        } elseif (isset($data['ipsResponse'])) {
            //环迅提现
            $xmlResult = new SimpleXMLElement($data['ipsResponse']);
            $channel_merchant_id = $xmlResult->argMerCode;
        } elseif (isset($data['reCode']) && isset($data['result'])) {
            //和易付
            if (isset($data['trxMerchantOrderno'])) {
                $mch_order_no = $data['trxMerchantOrderno'];
            } elseif (isset($data['merchantOrderNo'])) {
                $mch_order_no = $data['merchantOrderNo'];
            }
        } elseif (isset($data['seller_email']) && isset($data['buyer_id'])) {
            //赤龙支付宝
            $mch_order_no = $data['out_trade_no'];
        } elseif (isset($data['charset']) && isset($data['data'])) {
            //杉德总部
            $de_data = json_decode($data['data'], true);
            $mch_order_no = $de_data['body']['orderCode'];
        } elseif (isset($data['sdpayno']) && isset($data['sdorderno']) && isset($data['sdpayno1'])) {
            // 国华汇银
            Log::debug('[异步通知] 国华汇银 ' . json_encode($data));
            $mch_order_no = $data['sdpayno'];
        } elseif (isset($data['result_message']) && isset($data['result_code'])) {
            // 付呗
            Log::debug('[异步通知] 付呗 ' . json_encode($data));
            $de_data = json_decode($data['data'], true);
            $mch_order_no = $de_data['merchant_order_sn'];
        }elseif (isset($data['parter']) && isset($data['sysorderid'])) {
            // 裕支付
            Log::debug('[异步通知] 裕支付 ' . json_encode($data));
            $mch_order_no = $data['orderid'];
        }elseif (isset($data['mchtOrderId']) && isset($data['orderAmount'])) {
            // 开联通
            Log::debug('[异步通知] 开联通 ' . json_encode($data));
            $mch_order_no = $data['orderNo'];
        }elseif(isset($data['customerid'])){
            //易通付
            //{"status":"1","customerid":"11149","sdpayno":"2018092618081414868","sdorderno":"S120011809261808137125","total_fee":"0.01","paytype":"aliwap","remark":"","sign":"39715f26413e768ec49e3458f4328d4a"}
            Log::debug('[异步通知] 易通付 ' . json_encode($data));
            $mch_order_no = $data['sdorderno'];
        }elseif(isset($data['amt_type']) && isset($data['mch_id'])){
            // 畅支付
            Log::debug('[异步通知] 畅支付 ' . json_encode($data));
            $mch_order_no = $data['mch_order'];
        }

        // ===================================  取通道标识或通道商户号 ================== //
        if (!empty($mch_order_no)) {
            //有商户号
            $mch_order_no = strtoupper($mch_order_no);
            $order_no_initial = mb_substr($mch_order_no, 0, 1, 'utf-8');
            if ($order_no_initial == 'S') {
                //支付订单
                $order = Order::get(['order_no' => $mch_order_no]);
            } elseif ($order_no_initial == 'C') {
                //商户提现订单
                $order = MerchantWithdrawCash::get(['order_no' => $mch_order_no]);
                $mch_id = $order->mch_id;
            } elseif ($order_no_initial == 'P') {
                //三方商户号利润提现订单
                $order = ChannelMerchantCash::get(['order_no' => $mch_order_no]);
                $channel_merchant_id = $order->channel_mch_id;
            } else {
                return $this->setError(false, '订单类型错误');
            }
            if (empty($order)) {
                return $this->setError(false, '订单不存在');
            }
            $channel_str = $order->channel;
            $mch_id = $order->mch_id;

        } elseif (!empty($channel_merchant_id)) {
            //有渠道商户号

            //查询渠道商户号
            $channel_config = ChannelMerchant::where(['channel_mch_id' => $channel_merchant_id])->find();
            $channel_str = $channel_config->channel;
        } else {
            return $this->setError(false, '非法访问');
        }
        // ================================= 设置渠道配置 =================================== //
        $object = App::invokeClass(config('channel_class')[$channel_str]);
        if (!empty($mch_id)) {
            $_result = $object->setMchChannelConfig($mch_id);
        } elseif (!empty($channel_merchant_id)) {
            $_result = $object->setChannelConfig($channel_merchant_id);
        } else {
            return $this->setError(false, '非法访问');
        }
        if (!$_result) {
            $error = $object->getReturnMsg();
            return $this->setError(false, $error['message']);
        }
        $result = $object->notify($data);
        if (!$result) {
            $error = $object->getReturnMsg();
            return $this->setError(false, $error['message'], $error['code']);
        }

        // ================================== 拿到回调订单号，并判断类型执行对应方法 =======================//
        //先将字符串转大写，避免有些渠道自动转小写匹配不到订单问题
        $result['order_no'] = strtoupper($result['order_no']);
        //区分订单类型 S_支付订单   C_提现订单
        $order_no = $result['order_no'];
        $type = mb_substr($order_no, 0, 1, 'utf-8');
        if ($type == 'S') {
            //支付订单
            $res = $this->payNotify($channel_str, $result);
        } elseif ($type == 'C') {
            //提现订单
            $res = $this->cashNotify($channel_str, $result);
        } elseif ($type == 'P') {
            //三方商户号利润提现处理
            $channelReposttory = new ChannelReposttory();
            $res = $channelReposttory->cashNotify($channel_str, $result);
        } else {
            $res = $this->setError(false, '未知的订单类型');
        }
        if (!$res) return false;
        return $object->notifySuccessEcho($mch_order_no);
    }

    /**
     * 新支付异步通知处理
     * @param $channel_str
     * @param $result
     * @param int $is_send_notify
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function payNotify($channel_str, $result, $is_send_notify = 1)
    {

        $order_no = $result['order_no'];

        if ($result['status'] == 'success' || $result['status'] == 'fail') {

        } elseif ($result['status'] == 'wait') {
            return $this->setError(false, '等待支付中！', 3);
        } else {
            return $this->setError(false, '未知状态！', 3);
        }

        Db::startTrans();
        try {
            $order = Db::name('order')->where(['order_no' => $order_no])->lock(true)->find();
            if (empty($order)) {
                Db::rollback();
                return $this->setError(false, '找不到订单', 1);
            }
            if ($order['total_fee'] != $result['total_fee']) {
                Db::rollback();
                return $this->setError(false, '订单金额与发起支付金额不一致');
            }

            if ($order['status'] == 0) {
                $order_update = [];
                if (empty($order['channel_order_no'])) $order_update['channel_order_no'] = $result['channel_order_no'];

                //这里会出现并发问题，使用悲观锁锁住渠道账户当前记录
                $mch = Db::name('merchant_account')->where(['mch_id' => $order['mch_id'], 'channel' => $channel_str])->lock(true)->find();

                //查询三方渠道商户号账户表
                $channel_mch_balance = Db::name('channel_merchant_balance')->where(['channel_merchant' => $order['channel_merchant_no'], 'channel' => $order['channel']])->lock(true)->find();
                if (!empty($order['agents_id'])) {
                    // [代理商]
                    $agentsAccount = Db::name('agents_account')->where(['agents_id' => $order['agents_id'], 'channel' => $channel_str])->lock(true)->find();
                }
                if ($result['status'] == 'success') {
                    //订单修改信息
                    $order_update['status'] = 1;
                    $order_update['pay_time'] = isset($result['pay_time']) ? $result['pay_time'] : time();
                    $jiesuan_time = $this->getJiesuanTime($order);
                    if ($jiesuan_time) {
                        $order_update['jiesuan_time'] = $jiesuan_time;
                    }

                    //查询三方商户号配置
                    $channel_merchant = Db::name('channel_merchant')->where(['channel' => $order['channel'], 'channel_mch_id' => $order['channel_merchant_no']])->find();

                    if ($order['channel'] != 'chilong_alipay' || ($order['channel'] == 'chilong_alipay' && $channel_merchant['extend_1'] == 2)) {
                        //非支付宝当面付自有模式，进账
                        //实际到账金额
                        $money = round(bcsub($order['total_fee'], $order['service_charge'], 3), 2);

                        //==================== 商户 ===============
                        //商户账户更新
                        Db::name('merchant_account')
                            ->where(['id' => $mch['id']])
                            ->update([
                                'sum_balance' => ['exp', 'sum_balance + ' . $order['total_fee']], //总金额
                                'balance' => ['exp', 'balance + ' . $money], //余额
                                'unliquidated_money' => ['exp', 'unliquidated_money + ' . $money], //未结算金额
                                'service_charge' => ['exp', 'service_charge + ' . $order['service_charge']], //手续费
                            ]);
                        //增加商户账户余额明细
                        Db::name('merchant_balance_record')->insert([
                            'mch_id' => $order['mch_id'],
                            'type' => 1,
                            'channel' => $order['channel'],
                            'money' => $money,
                            'befor_money' => $mch['balance'],
                            'after_money' => $mch['balance'] + $money,
                            'remark' => '订单[' . $order['order_no'] . ']入账' . $money . '元',
                            'created_at' => time()
                        ]);

                        //==================== 三方商户号 ==============
                        //三方商户号账户更新
                        Db::name('channel_merchant_balance')
                            ->where(['id' => $channel_mch_balance['id']])
                            ->update([
                                'sum_balance' => ['exp', 'sum_balance + ' . $order['net_profit']], //总金额
                                'balance' => ['exp', 'balance + ' . $order['net_profit']], //余额
                                'unliquidated_money' => ['exp', 'unliquidated_money + ' . $order['net_profit']], //未结算金额
                            ]);
                        //增加三方商户号利润账户记录
                        Db::name('channel_merchant_balance_record')->insert([
                            'channel_merchant' => $order['channel_merchant_no'],
                            'type' => 1,
                            'channel' => $order['channel'],
                            'money' => $order['net_profit'],
                            'befor_money' => $channel_mch_balance['balance'],
                            'after_money' => $channel_mch_balance['balance'] + $order['net_profit'],
                            'remark' => '订单[' . $order['order_no'] . ']入账' . $order['net_profit'] . '元',
                            'created_at' => time()
                        ]);


                        // ================ 代理商 ====================
                        if (!empty($order['agents_id'])) {
                            Db::name('agents_account')
                                ->where(['id' => $agentsAccount['id']])
                                ->update([
                                    'sum_balance' => ['exp', 'sum_balance + ' . $order['agents_income']], //总金额
                                    'unliquidated_money' => ['exp', 'unliquidated_money + ' . $order['agents_income']], //未结算金额
                                ]);

                            //增加日志
                            Db::name('agents_balance_record')->insert([
                                'agents_id' => $agentsAccount['agents_id'],
                                'salesman_id' => $order['salesman_id'],
                                'type' => 1,
                                'channel' => $order['channel'],
                                'money' => $order['agents_income'],
                                'before_money' => $agentsAccount['sum_balance'],
                                'after_money' => $agentsAccount['sum_balance'] + $order['agents_income'],
                                'remark' => '商户-' . $order['mch_id'] . '-订单[' . $order['order_no'] . ']代理入账' . $order['agents_income'] . '元',
                                'created_at' => time()
                            ]);
                        }

                    } else {
                        //支付宝当面付自有模式，不进涨，直接改为已结算
                        $order_update['jiesuan_status'] = 1;
                        $order_update['fact_jiesuan_time'] = time();

                        //该模式为预扣费模式
                        $poundage = $order['service_charge'];
                        $mch_info = Db::name('merchant')->lock(true)->find($order['mch_id']);
                        $after_poundage = round(bcsub($mch_info['deducting_fee'], $poundage, 3), 2);
                        Db::name('merchant')
                            ->where(['id' => $mch_info['id']])
                            ->update([
                                'deducting_fee' => $after_poundage
                            ]);

                        //写入余额记录日志
                        Db::name('merchant_balance_record')->insert([
                            'mch_id' => $mch_info['id'],
                            'record_type' => 2,
                            'type' => 2,
                            'money' => $order['service_charge'],
                            'after_money' => $after_poundage,
                            'befor_money' => $mch_info['deducting_fee'],
                            'remark' => '订单[' . $order['order_no'] . ']支付' . $order['total_fee'] . '元，扣除手续费' . $poundage,
                            'created_at' => time()
                        ]);
                    }
                } elseif ($result['status'] == 'fail') {
                    $order_update['status'] = -1;
                }
                Db::name('order')->where(['id' => $order['id']])->update($order_update);
            }

            Db::commit();

        } catch (\Exception $exception) {
            Db::rollback();
            Log::error('系统异常=》' . $exception->getMessage() . '|' . $exception->getTraceAsString());
            return $this->setError(false, '系统异常', 1);
        }

        if ($is_send_notify == 1 && $order['notify_status'] != 'yes') {
            //发送通知
            $result = $this->sendNotify($order_no);
            if ($result) {
                return $this->setError(true, '处理成功');
            } else {
                return $this->setError(false, '发送异步通知失败', 4);
            }
        } else {
            return $this->setError(true, '处理成功');
        }
    }

    /**
     * 计算当前订单的结算时间
     * @param $order
     * @return bool|false|int|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function getJiesuanTime($order)
    {

        $jiesuan_time = false;

        $date = date('Y-m-d');
        $delay_time = $order['jiesuan_delay_time'];

        //23点时间戳
        $e_time = strtotime($date) + (3600 * 23);
        if (time() >= $e_time && in_array($order['channel'], ['xtenpay', 'xtenpay_quick', 'sandpay'])) {
            //如果是杉德通道。且过了今天23点，则按第二天成交的订单结算
            $date = date('Y-m-d', strtotime('+1 day')); //第二天凌晨时间戳
        }

        switch ($order['jiesuan_clear_type']) {
            case 1: // T1
                //查询下一个工作日
                $today = Db::name('calendar')->where(['date' => $date])->find();
                $next_work_day = Db::name('calendar')->where(['id' => ['gt', $today['id']], 'is_work' => 1])->find();
                $jiesuan_time = strtotime($next_work_day['date']) + $delay_time;
                break;
            case 2: // T0
                //查询今天是不是工作日
                $today = Db::name('calendar')->where(['date' => $date])->find();
                if ($today['is_work'] == 1) {
                    $jiesuan_time = time() + $delay_time;
                } else {
                    //下一个工作日
                    $next_work_day = Db::name('calendar')->where(['id' => ['gt', $today['id']], 'is_work' => 1])->find();
                    $jiesuan_time = strtotime($next_work_day['date']) + $delay_time;
                }
                break;
            case 3: //D1
                $jiesuan_time = strtotime($date) + 86400 + $delay_time;
                break;
            case 4: //D0
                $jiesuan_time = time() + $delay_time;
                break;
        }

        return $jiesuan_time;
    }

    /**
     * 支付异步通知处理
     * @param $result
     * @return mixed
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    private function payNotifyOld($channel_str, $result, $is_send_notify = 1)
    {

        //查询渠道配置
        $pay_channel_config = Db::name('pay_channel_config')->where(['class_namespace' => $channel_str])->find();
        if (empty($pay_channel_config)) {
            return $this->setError(false, '渠道配置不存在');
        }
        if ($result['status'] == 'success' || $result['status'] == 'fail') {

        } elseif ($result['status'] == 'wait') {
            return $this->setError(false, '等待支付中！', 3);
        } else {
            return $this->setError(false, '未知状态！', 3);
        }

        $order_no = $result['order_no'];
        //查询订单
        Db::startTrans();
        try {
            $order = Db::name('order')->where(['order_no' => $order_no])->lock(true)->find();
            if (empty($order)) {
                Db::rollback();
                //找不到订单
                return $this->setError(false, '找不到订单', 1);
            }
            if ($order['total_fee'] != $result['total_fee']) {
                Db::rollback();
                Log::error('订单金额与发起支付金额不一致=》' . json_encode($result));
                return $this->setError(false, '订单金额与发起支付金额不一致');
            }

            if ($order['status'] == 0) {
                //未支付
                $order_update = [];

                if (empty($order['channel_order_no'])) {
                    Db::name('order')->where(['id' => $order['id']])->setField('channel_order_no', $result['channel_order_no']);
                }
                $pay_time = time();
                if (isset($result['pay_time'])) {
                    $pay_time = $result['pay_time'];
                }

                //更改状态
                if ($result['status'] == 'success') {
                    $order_update['status'] = 1;
                    $order_update['pay_time'] = $pay_time;
                } elseif ($result['status'] == 'fail') {
                    $order_update['status'] = -1;
                }

                //这里会出现并发问题，使用悲观锁锁住渠道账户当前记录
                $mch = Db::name('merchant_account')->where(['mch_id' => $order['mch_id'], 'channel' => $channel_str])->lock(true)->find();

                //查询三方渠道商户号账户表
                $channel_mch_balance = Db::name('channel_merchant_balance')
                    ->where([
                        'channel_merchant' => $order['channel_merchant_no'],
                        'channel' => $order['channel']
                    ])
                    ->find();
                // [代理商]
                if (!empty($order['agents_id'])) {
                    $agentsAccount = Db::name('agents_account')
                        ->where(['agents_id' => $order['agents_id'], 'channel' => $channel_str])
                        ->lock(true)
                        ->find();
                    $agentsInfo = Db::name('agents')
                        ->where('id', '=', $order['agents_id'])
                        ->lock(true)
                        ->find();
                }
                //更新订单状态
                Db::name('order')->where(['id' => $order['id']])->update($order_update);
                if ($result['status'] == 'success') {
                    $money = round(bcsub($order['total_fee'], $order['service_charge'], 3), 2); //实际到账金额

                    // [代理商] 实际到账金额
                    if (!empty($order['agents_id'])) {
                        $agentsMoney = round($order['agents_income'], 2);
                        $agentBalance = $agentsAccount['sum_balance'];
                        $agents_withdraw_cash_balance = $agentsMoney;
                        $agents_tomorrow_withdraw_cash_balance = 0;
                    }

                    //更改商户账户金额
                    $balance = $mch['balance'];
                    $add_withdraw_cash_balance = $money;
                    $mch_add_withdraw_cash_balance = $order['net_profit'];
                    $add_tomorrow_withdraw_cash_balance = 0;
                    $mch_add_tomorrow_withdraw_cash_balance = 0;
                    if ($pay_channel_config['t1_balance_ratio'] > 0) {

                        //商户账户余额
                        $add_withdraw_cash_balance = bcmul($money, (1 - $pay_channel_config['t1_balance_ratio']), 2);
                        $add_tomorrow_withdraw_cash_balance = bcsub($money, $add_withdraw_cash_balance, 2);

                        //三方商户利润余额
                        $mch_add_withdraw_cash_balance = bcmul($order['net_profit'], (1 - $pay_channel_config['t1_balance_ratio']), 2);
                        $mch_add_tomorrow_withdraw_cash_balance = bcsub($order['net_profit'], $mch_add_withdraw_cash_balance, 2);

                        // [代理商]
                        if (!empty($order['agents_id'])) {
                            // 可结算金额 = 代理收入 * 1 - T1结算比例
                            $agents_withdraw_cash_balance = bcmul($agentsMoney, (1 - $pay_channel_config['t1_balance_ratio']), 2);
                            // 今日冻结结算金额 = 代理收入 - 可结算金额
                            $agents_tomorrow_withdraw_cash_balance = bcsub($agentsMoney, $agents_withdraw_cash_balance, 2);
                        }
                    }

                    //三方商户号账户更新
                    $channel_mch_account_update = [
                        'sum_balance' => bcadd($channel_mch_balance['sum_balance'], $order['net_profit'], 3)
                    ];

                    //商户账户更新
                    $mch_account_update = [
                        'sum_balance' => bcadd($mch['sum_balance'], $order['total_fee'], 3), //总金额
                        'service_charge' => bcadd($mch['service_charge'], $order['service_charge'], 3), //手续费
                    ];
                    //查询三方商户号配置
                    $channel_merchant = Db::name('channel_merchant')
                        ->where([
                            'channel' => $order['channel'],
                            'channel_mch_id' => $order['channel_merchant_no']
                        ])->find();
                    if ($order['channel'] != 'chilong_alipay' || ($order['channel'] == 'chilong_alipay' && $channel_merchant['extend_1'] == 2)) {
                        $mch_account_update['balance'] = bcadd($mch['balance'], $money, 3); //可用余额
                        $channel_mch_account_update['balance'] = bcadd($channel_mch_balance['balance'], $order['net_profit'], 2);
                    }

                    // [代理商] 商户更新
                    $agents_account_update = [];
                    if (!empty($order['agents_id'])) {
                        $agents_account_update['id'] = $agentsAccount['id'];
                        $agents_account_update['sum_balance'] = bcadd($agentsAccount['sum_balance'], $order['agents_income'], 3);
                    }

                    if ($pay_channel_config['t1_balance_ratio'] == 0 && $pay_channel_config['d0_balance_time'] > 0) {
                        //如果是D0，且D0有延迟解冻时间，则金额全部进入冻结账户，等待n时间后自动解冻
                        if ($order['channel'] != 'chilong_alipay' || ($order['channel'] == 'chilong_alipay' && $channel_merchant['extend_1'] == 2)) {
                            $mch_account_update['today_withdraw_cash_balance'] = bcadd($mch['today_withdraw_cash_balance'], $money, 2);
                            $channel_mch_account_update['today_withdraw_cash_balance'] = bcadd($channel_mch_balance['today_withdraw_cash_balance'], $order['net_profit'], 2);
                        }
                        // [代理商]
                        if (!empty($order['agents_id'])) {
                            $agents_account_update['today_withdraw_cash_balance'] = bcadd($agentsAccount['today_withdraw_cash_balance'], $order['agents_income'], 2);
                            $agents_withdraw_cash_balance = 0;
                        }
                    } else {
                        if ($order['channel'] != 'chilong_alipay' || ($order['channel'] == 'chilong_alipay' && $channel_merchant['extend_1'] == 2)) {
                            $mch_account_update['withdraw_cash_balance'] = bcadd($mch['withdraw_cash_balance'], $add_withdraw_cash_balance, 2);
                            $mch_account_update['today_withdraw_cash_balance'] = bcadd($mch['today_withdraw_cash_balance'], $add_tomorrow_withdraw_cash_balance, 2);

                            $channel_mch_account_update['withdraw_cash_balance'] = bcadd($channel_mch_balance['withdraw_cash_balance'], $mch_add_withdraw_cash_balance, 2);
                            $channel_mch_account_update['today_withdraw_cash_balance'] = bcadd($channel_mch_balance['today_withdraw_cash_balance'], $mch_add_tomorrow_withdraw_cash_balance, 2);
                        }

                        //[代理商]
                        if (!empty($order['agents_id'])) {
                            $agents_account_update['withdraw_cash_balance'] = bcadd($agentsAccount['withdraw_cash_balance'], $agents_withdraw_cash_balance, 2);
                            $agents_account_update['today_withdraw_cash_balance'] = bcadd($agentsAccount['today_withdraw_cash_balance'], $agents_tomorrow_withdraw_cash_balance, 2);
                        }
                    }

                    Db::name('merchant_account')->where(['id' => $mch['id']])->update($mch_account_update);
                    Db::name('channel_merchant_balance')->where(['id' => $channel_mch_balance['id']])->update($channel_mch_account_update);

                    // ----------------------------------------[代理商]
                    // 如果是代理商
                    if (!empty($order['agents_id'])) {
                        if ($agents_withdraw_cash_balance > 0) {
                            //D0结算金额
                            $totalAmount = bcadd($agentsInfo['total_amount'], $agents_withdraw_cash_balance, 2);
                            Db::name('agents')->update([
                                'id' => $order['agents_id'],
                                'total_amount' => $totalAmount
                            ]);
                        }

                        Db::name('agents_account')->update($agents_account_update);
                        Db::name('agents_balance_record')->insert([
                            'agents_id' => $agentsAccount['agents_id'],
                            'salesman_id' => $order['salesman_id'],
                            'type' => 1,
                            'channel' => $order['channel'],
                            'money' => $agentsMoney,
                            'before_money' => $agentBalance,
                            'after_money' => $agents_account_update['sum_balance'],
                            'remark' => '商户-' . $order['mch_id'] . '-订单[' . $order['order_no'] . ']代理入账' . $agentsMoney . '元',
                            'created_at' => time()
                        ]);
                    }
                    // ----------------------------------------[代理商]

                    if ($order['channel'] != 'chilong_alipay' || ($order['channel'] == 'chilong_alipay' && $channel_merchant['extend_1'] == 2)) {
                        //增加余额明细
                        Db::name('merchant_balance_record')->insert([
                            'mch_id' => $order['mch_id'],
                            'type' => 1,
                            'channel' => $order['channel'],
                            'money' => $money,
                            'befor_money' => $balance,
                            'after_money' => $mch_account_update['balance'],
                            'remark' => '订单[' . $order['order_no'] . ']入账' . $money . '元',
                            'created_at' => time()
                        ]);

                        //增加三方商户号利润账户记录
                        Db::name('channel_merchant_balance_record')->insert([
                            'channel_merchant' => $order['channel_merchant_no'],
                            'type' => 1,
                            'channel' => $order['channel'],
                            'money' => $order['net_profit'],
                            'befor_money' => $channel_mch_balance['balance'],
                            'after_money' => $channel_mch_account_update['balance'],
                            'remark' => '订单[' . $order['order_no'] . ']入账' . $order['net_profit'] . '元',
                            'created_at' => time()
                        ]);
                    }

                    // ========= 2018-6-16 17:26 增加赤龙支付宝当面付预扣除手续费
                    if ($order['channel'] == 'chilong_alipay' && $channel_merchant['extend_1'] == 1) {
                        $poundage = $order['service_charge'];
                        $mch_info = Db::name('merchant')->lock(true)->find($order['mch_id']);
                        $after_poundage = round(bcsub($mch_info['deducting_fee'], $poundage, 3), 2);
                        Db::name('merchant')
                            ->where(['id' => $mch_info['id']])
                            ->update([
                                'deducting_fee' => $after_poundage
                            ]);

                        //写入余额记录日志
                        Db::name('merchant_balance_record')->insert([
                            'mch_id' => $mch_info['id'],
                            'record_type' => 2,
                            'type' => 2,
                            'money' => $poundage,
                            'after_money' => $after_poundage,
                            'befor_money' => $mch_info['deducting_fee'],
                            'remark' => '订单[' . $order['order_no'] . ']支付' . $order['total_fee'] . '元，扣除手续费' . $poundage,
                            'created_at' => time()
                        ]);
                    }
                    // end
                }
            }
            Db::commit();

        } catch (\Exception $exception) {
            Db::rollback();
            Log::error('系统异常=》' . $exception->getMessage() . '|' . $exception->getTraceAsString());
            return $this->setError(false, '系统异常', 1);
        }

        if ($is_send_notify == 1 && $order['notify_status'] != 'yes') {
            //发送通知
            $result = $this->sendNotify($order_no);
            if ($result) {
                return $this->setError(true, '处理成功');
            } else {
                return $this->setError(false, '发送异步通知失败', 4);
            }
        } else {
            return $this->setError(true, '处理成功');
        }
    }

    /**
     * 提现结果异步通知
     * @param $channel_str
     * @param $result
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function cashNotify($channel_str, $result)
    {
        //查询订单
        Db::startTrans();
        $order = Db::name('merchant_withdraw_cash')
            ->where(['order_no' => $result['order_no']])
            ->lock(true)
            ->find();
        if (empty($order)) {
            Db::rollback();
            return $this->setError(false, '找不到订单', 1);
        }
        Db::commit();

        if ($order['status'] == 1) {
            //未处理
            $order_update = [];
            if (empty($order['channel_order_no'])) {
                $order_update['channel_order_no'] = $result['channel_order_no'];
            }
            //更改状态
            if ($result['status'] == 'success') {
                $order_update['status'] = 2;
                $order_update['into_time'] = time();
            } elseif ($result['status'] == 'fail') {
                $order_update['status'] = 3;
            }
            //如果有错误备注，则更新
            if (!empty($result['remark'])) {
                $order_update['remark'] = $result['remark'];
            }

            Db::startTrans(); //开启事务
            //查询通道账户，并加上悲观锁
            $mch = Db::name('merchant_account')->where([
                'mch_id' => $order['mch_id'],
                'channel' => $channel_str
            ])->lock(true)->find();

            try {
                //更新订单状态
                Db::name('merchant_withdraw_cash')->where(['id' => $order['id']])->update($order_update);

                if ($result['status'] == 'success') {
                    //成功，扣除提现中，增加已提现金额
                    Db::name('merchant_account')
                        ->where(['id' => $mch['id']])
                        ->update([
                            'withdraw_cash_ing' => bcsub($mch['withdraw_cash_ing'], $order['amount'], 2),
                            'withdraw_cash' => bcadd($mch['withdraw_cash'], $order['amount'], 2),
                        ]);
                } elseif ($result['status'] == 'fail') {
                    //失败，扣除提现中金额，增加用户余额，增加可提现金额，增加余额明细
                    $balance = $mch['balance'];
                    if ($channel_str == 'ips') {
                        //环讯
                        //扣除提现中金额
                        Db::name('merchant_account')
                            ->where(['id' => $mch['id']])
                            ->update([
                                'withdraw_cash_ing' => bcsub($mch['withdraw_cash_ing'], $order['amount'], 2)
                            ]);
                        //增加渠道商户客户号余额
                        Db::name('channel_merchant_account')->where([
                            'mch_id' => $order['mch_id'],
                            'channel' => $order['channel'],
                            'channel_mch_id' => $order['channel_mch_id'],
                            'account' => $order['channel_mch_account']
                        ])->setInc('balance', $order['total_fee']);

                    } else {
                        //用户提现中金额转余额
                        $merchant_account_update = [
                            'balance' => bcadd($balance, $order['total_fee'], 2),
                            'withdraw_cash_ing' => bcsub($mch['withdraw_cash_ing'], $order['amount'], 2),
                            'withdraw_cash_balance' => bcadd($mch['withdraw_cash_balance'], $order['total_fee'], 2)
                        ];
                        Db::name('merchant_account')
                            ->where(['id' => $mch['id']])
                            ->update($merchant_account_update);

                        //增加余额明细
                        Db::name('merchant_balance_record')->insert([
                            'mch_id' => $order['mch_id'],
                            'type' => 1,
                            'money' => $order['total_fee'],
                            'channel' => $order['channel'],
                            'befor_money' => $balance,
                            'after_money' => $merchant_account_update['balance'],
                            'remark' => '订单[' . $order['order_no'] . ']入账' . $order['total_fee'] . '元，包含退还提现手续费',
                            'created_at' => time()
                        ]);
                    }
                }
                //提交事务
                Db::commit();
                return $this->setError(true, '提现处理成功');
            } catch (\Exception $exception) {
                Log::error('ips提现处理失败=>' . $exception->getTraceAsString());
                Db::rollback();
                return $this->setError(false, '系统异常');
            }
        } elseif ($order['status'] == 2) {
            return $this->setError(true, '该提现订单已处理,提现成功');
        }elseif ($order['status'] == 3) {
            return $this->setError(true, '该提现订单已处理,提现失败');
        } else {
            return $this->setError(false, '订单状态错误');
        }
    }

    /**
     * 利润提现异步通知处理
     * @param $channel_str
     * @param $result
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function profitCashNotify($channel_str, $result)
    {
        Db::startTrans();
        $order = Db::name('channel_merchant_cash')
            ->where(['order_no' => $result['order_no']])
            ->lock(true)
            ->find();
        if (empty($order)) {
            Db::rollback();
            return $this->setError(false, '找不到订单', 1);
        }
        Db::commit();

        if ($order['status'] == 1) {
            //未处理
            $order_update = [];
            if (empty($order['channel_order_no'])) {
                $order_update['channel_order_no'] = $result['channel_order_no'];
            }
            //更改状态
            if ($result['status'] == 'success') {
                $order_update['status'] = 2;
                $order_update['into_time'] = time();
            } elseif ($result['status'] == 'fail') {
                $order_update['status'] = 3;
            }
            //如果有错误备注，则更新
            if (!empty($result['remark'])) {
                $order_update['remark'] = $result['remark'];
            }

            Db::startTrans(); //开启事务

            try {
                //查询三方通道账户，并加上悲观锁
                $mch = Db::name('channel_merchant_balance')->where([
                    'channel_merchant' => $order['channel_mch_id'],
                    'channel' => $channel_str
                ])->lock(true)->find();

                //更新订单状态
                Db::name('channel_merchant_cash')->where(['id' => $order['id']])->update($order_update);

                if ($result['status'] == 'success') {
                    //成功，扣除提现中，增加已提现金额
                    Db::name('channel_merchant_balance')
                        ->where(['id' => $mch['id']])
                        ->update([
                            'withdraw_cash_ing' => bcsub($mch['withdraw_cash_ing'], $order['amount'], 2),
                            'withdraw_cash' => bcadd($mch['withdraw_cash'], $order['amount'], 2),
                        ]);
                } elseif ($result['status'] == 'fail') {
                    //失败，扣除提现中金额，增加用户余额，增加可提现金额，增加余额明细
                    $balance = $mch['balance'];
                    if ($channel_str == 'ips') {
                        //环讯
                        //扣除提现中金额
                        Db::name('channel_merchant_balance')
                            ->where(['id' => $mch['id']])
                            ->update([
                                'withdraw_cash_ing' => bcsub($mch['withdraw_cash_ing'], $order['amount'], 2)
                            ]);
                        //增加渠道商户客户号余额
                        Db::name('channel_merchant_account')->where([
                            'mch_id' => $order['mch_id'],
                            'channel' => $order['channel'],
                            'channel_mch_id' => $order['channel_mch_id'],
                            'account' => $order['channel_mch_account']
                        ])->setInc('balance', $order['total_fee']);

                    } else {
                        //用户提现中金额转余额
                        $merchant_account_update = [
                            'balance' => bcadd($balance, $order['total_fee'], 2),
                            'withdraw_cash_ing' => bcsub($mch['withdraw_cash_ing'], $order['amount'], 2),
                            'withdraw_cash_balance' => bcadd($mch['withdraw_cash_balance'], $order['total_fee'], 2)
                        ];
                        Db::name('channel_merchant_balance')
                            ->where(['id' => $mch['id']])
                            ->update($merchant_account_update);

                        //增加余额明细
                        Db::name('channel_merchant_balance_record')->insert([
                            'channel_merchant' => $order['channel_mch_id'],
                            'channel' => $order['channel_order_no'],
                            'type' => 1,
                            'money' => $order['total_fee'],
                            'after_money' => $balance,
                            'befor_money' => $merchant_account_update['balance'],
                            'remark' => '订单[' . $order['order_no'] . ']提现失败退回' . $order['total_fee'] . '元，包含退还提现手续费',
                            'created_at' => time()
                        ]);
                    }
                }

                //提交事务
                Db::commit();

                return $this->setError(true, '提现处理成功');

            } catch (\Exception $exception) {
                Log::error('提现处理失败=>' . $exception->getTraceAsString());
                Db::rollback();
                return $this->setError(false, '系统异常');
            }
        } elseif ($order['status'] == 2) {
            return $this->setError(true, '该提现订单已处理');
        } else {
            return $this->setError(false, '订单状态错误');
        }
    }

    /**
     * 发送通知 type = 1支付自动回调结果   2手动发送回调
     * @param $order_no
     * @param int $type
     * @return bool|mixed
     * @throws \think\exception\DbException
     */
    public function sendNotify($order_no, $type = 1)
    {
        $order = Order::get(['order_no' => $order_no]);
        $mch = Merchant::get($order->mch_id);
        if (empty($order->notify_url)) {
            return true;
        }

        $send_data = [
            'order_no' => (string)$order->order_no,
            'mch_order_no' => (string)$order->mch_order_no,
            'goods' => (string)$order->goods,
            'total_fee' => (float)$order->total_fee,
            'payment' => (string)$order->payment,
            'rate' => (float)$order->rate,
            'service_charge' => (float)$order->service_charge,
            'status' => (int)$order->status,
            'create_time' => date('Y-m-d H:i:s', $order->create_time),
            'pay_time' => date('Y-m-d H:i:s', $order->pay_time),
        ];

        $sign = $this->sign($send_data, $mch);
        $send_data['sign'] = $sign;

        //循环url编码
        foreach ($send_data as &$item) {
            $item = urlencode($item);
        }
        unset($item);

        Log::debug('发送异步通知：(' . $order->notify_url . ')' . json_encode($send_data));
        $result = curl_post($order->notify_url, $send_data);
        Log::debug('异步通知返回结果：' . $result);
        $result_str = strtolower($result);
        $result_str = trim($result_str, '"');

        if ($result_str == 'success') {
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
     * @param $mch
     * @return bool|string
     */
    public function sign($data, $mch)
    {

        ksort($data);
        $params_str = urldecode(http_build_query($data));
        $params_str = $params_str . '&key=' . $mch->key;

        return md5($params_str);
    }

    /**
     * 提现
     * @param $mch_id
     * @param $amount
     * @param $card
     * @param $account_id
     * @param int $type 1后台提现  2接口代付
     * @return mixed
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function cash($mch_id, $amount, $card, $account_id, $type = 1)
    {

        //如果是代付，判断代付订单是否重复
        if ($type == 2) {
            $cash_row = MerchantWithdrawCash::get(['mch_id' => $mch_id, 'mch_order_no' => $card->mch_order_no]);
            if ($cash_row) {
                return $this->setError(false, '代付订单号重复');
            }
        }
        $account = \app\common\model\MerchantAccount::get(['id' => $account_id, 'mch_id' => $mch_id]);
        if (empty($account)) {
            return $this->setError(false, '账户不存在');
        }
        //查询手续费
        $pay_channel_config = PayChannelConfig::get(['class_namespace' => $account->channel]);
        if ($pay_channel_config->status == 0) {
            return $this->setError(false, '通道已关闭');
        }
        // 单笔手续费
        $service_charge = '';
        if ($card->acc_attr == 2) {
            //对私
            $service_charge = $pay_channel_config->cash_poundage;
            $total_fee = bcadd($amount, $pay_channel_config->cash_poundage, 2);
        } elseif ($card->acc_attr == 1) {
            //对公
            $service_charge = $pay_channel_config->cash_public_poundage;
            $total_fee = bcadd($amount, $pay_channel_config->cash_public_poundage, 2);
        } else {
            return $this->setError(false, '提现类型错误');
        }

        if (!in_array($pay_channel_config->cash_type, [1, 2])) {
            return $this->setError(false, '提现方式配置错误');
        }
        if ($total_fee > $account->withdraw_cash_balance) {
            return $this->setError(false, '可提现余额不足');
        }

        if ($this->systemConfig['CASH_MINI_MONEY'] > $amount) {
            return $this->setError(false, '最低提现金额为 ' . $this->systemConfig['CASH_MINI_MONEY'] . ' 元');
        }

        //需要银行编码的额外处理
        if (in_array($card->channel, ['heepay', 'heepay_wechat', 'allscore', 'saas', 'chemstar'])) {
            //汇付宝，取出银行信息
            $bank_info = \app\common\model\ChannelBank::get($card->acc_bank);
            if (empty($bank_info)) {
                return $this->setError(false, '银行信息找不到');
            }
            $acc_bank = $bank_info->bank_name;
            $acc_bank_code = $bank_info->bank_code;

            if (in_array($card->channel, ['heepay', 'heepay_wechat', 'saas'])) {
                //汇付宝的取出专属城市
                //取出省份
                $province_info = BankCity::get($card->acc_province);
                if (empty($province_info)) {
                    return $this->setError(false, '银行所在省份信息找不到');
                }
                $acc_province = $province_info->city_name;
                //取出城市
                $city_info = BankCity::get($card->acc_city);
                if (empty($city_info)) {
                    return $this->setError(false, '银行所在城市信息找不到');
                }
                $acc_city = $city_info->city_name;

                //和壹付平台代付 省/城市编码
                if ($card->channel == 'saas') {
                    $acc_city = $city_info->city_code;
                    $acc_province = $province_info->city_code;
                }
            } else {
                $acc_city = $card->acc_city;
                $acc_province = $card->acc_province;
            }
        } else {
            //其他渠道
            $acc_bank = $card->acc_bank;
            $acc_bank_code = '';
            $acc_city = $card->acc_city;
            $acc_province = $card->acc_province;
        }

        $order_no = 'C' . $mch_id . date('ymdHis') . rand(0, 9);

        $data = [
            'acc_attr' => $card->acc_attr,
            'acc_bankno' => $card->acc_bankno,
            'acc_bank' => $acc_bank,
            'acc_bank_code' => $acc_bank_code,
            'acc_card' => $card->acc_card,
            'acc_province' => $acc_province,
            'acc_city' => $acc_city,
            'acc_name' => $card->acc_name,
            'acc_subbranch' => $card->acc_subbranch,
            'acc_idcard' => $card->acc_idcard,
            'acc_mobile' => $card->acc_mobile,
            'amount' => bcadd($amount, 0, 2),
            'order_no' => $order_no,
            'type' => $type
        ];
        if (empty($this->channelClass[$account->channel])) {
            return $this->setError(false, '平台方错误，找不到渠道配置');
        }
        $result['channel_order_no'] = '';

        $data['mch_id'] = $mch_id;
        $data['total_fee'] = $total_fee;
        $data['service_charge'] = $service_charge;
        $data['channel'] = $account->channel;
        $data['channel_order_no'] = $result['channel_order_no'];
        $data['created_at'] = time();
        $data['updated_at'] = time();

        $data['_cash_type'] = 'old'; //旧版提现

        //如果是代付，则添加代付订单号
        if ($type == 2 && isset($card->mch_order_no)) {
            $data['mch_order_no'] = $card->mch_order_no;
        }

        $object = App::invokeClass($this->channelClass[$card->channel]);
        $_result = $object->setMchChannelConfig($mch_id);
        if (!$_result) {
            $error = $object->getReturnMsg();
            return $this->setError(false, $error['message'], $error['code']);
        }

        Db::startTrans();
        try {
            $merchant_account = Db::name('merchant_account')->lock(true)->find($account->id);
            if ($pay_channel_config->cash_type == 1) {
                //接口自动提现\
                $result = $object->cash($data);
                if (!$result) {
                    $error = $object->getReturnMsg();
                    Log::error($error['message']);
                    return $this->setError(false, $error['message'], $error['code']);
                }

                $data['channel_order_no'] = $result['channel_order_no'];
                $data['status'] = 1;
                $data['channel_mch_id'] = $object->mchChannelConfig->channel_mch_id;
                if (isset($result['create_time'])) $data['created_at'] = $result['create_time'];
            } else {
                $data['channel_order_no'] = '';
                $data['status'] = 0;
                $data['channel_mch_id'] = $object->mchChannelConfig->channel_mch_id;
            }

            if (isset($data['_cash_type'])){
                unset($data['_cash_type']);
            }
            //增加提现订单
            Db::name('merchant_withdraw_cash')->insert($data);

            //扣除账户余额，增加提现中金额
            $balance = $merchant_account['balance'];
            $merchant_account_update = [
                'balance' => $balance - $total_fee,
                'withdraw_cash_balance' => $merchant_account['withdraw_cash_balance'] - $total_fee,
                'withdraw_cash_ing' => $merchant_account['withdraw_cash_ing'] + $data['amount']
            ];
            Db::name('merchant_account')->where(['id' => $account->id])->update($merchant_account_update);

            //增加账户余额明细
            Db::name('merchant_balance_record')->insert([
                'mch_id' => $mch_id,
                'type' => 2,
                'money' => $total_fee,
                'channel' => $account->channel,
                'befor_money' => $balance,
                'after_money' => $merchant_account_update['balance'],
                'remark' => '订单[' . $order_no . ']申请提现扣除' . $data['amount'] . '元, 手续费' . $pay_channel_config->cash_poundage . '元',
                'created_at' => time()
            ]);

            Db::commit();

            return [
                'order_no' => $data['order_no'],
                'channel_order_no' => $data['channel_order_no'],
            ];

        } catch (\Exception $e) {
            Db::rollback();
            Log::error('[新]提现提价交败=》' . $e->getMessage());
            return $this->setError(false, '系统异常，请稍后再试', 500);
        }
    }

    /**
     * [新提现] @add Tinywan
     * @param array $params
     * @param int $version
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function cashNew($params, $version = 1)
    {
        Log::debug('[新提现] API 接口请求参数 ' . json_encode($params));
        // 参数过滤验证
        $vail_rule = [
            'mch_id' => 'require',
            'channel' => 'require',
            'order_sn' => 'require',
            'amount' => 'require|between:1,50000',
            'acc_attr' => 'require',
            'acc_card' => 'require',
            'acc_name' => 'require',
            'acc_bank' => 'require',
            'acc_province' => 'require',
            'acc_city' => 'require',
            'acc_subbranch' => 'require',
            'acc_mobile' => 'require',
        ];
        $vail_field = [
            'mch_id' => '商户号',
            'channel' => '渠道名称',
            'order_sn' => '商户订单号',
            'amount' => '提现金额',
            'acc_attr' => '账户属性',
            'acc_card' => '收款人卡号',
            'acc_name' => '收款人账户名',
            'acc_bank' => '收款人银行名称',
            'acc_province' => '收款人开户省份',
            'acc_city' => '收款人开户城市',
            'acc_subbranch' => '收款账户开户行名称',
            'acc_mobile' => '手机号码',
        ];
        $message = [
            'mch_id.require' => '商户号不能为空',
            'channel.require' => '渠道名称不能为空',
            'order_sn.require' => '商家订单号不能为空',
            'goods.require' => '商品名不能为空',
            'amount.require' => '金额',
            'acc_attr.require' => '账户属性不能为空',
            'acc_card.require' => '收款人账户号不能为空',
            'acc_card.number' => '收款人账户号必须是数字',
            'acc_name.require' => '收款人账户名不能为空',
            'acc_bank.require' => '收款人银行名称不能为空',
            'acc_province.require' => '收款人开户省份不能为空',
            'acc_city.require' => '收款人开户城市不能为空',
            'acc_subbranch.require' => '收款银行名称不能为空',
            'acc_mobile.require' => '手机号码不能为空',
        ];

        $validate = new Validate($vail_rule, $message, $vail_field);
        if (!$validate->check($params)) {
            return $this->setError(false, $validate->getError());
        }
        // 黑白名单
        $ip = RedisLock::getRemoteIp();
        Log::debug('[新提现] 远程IP地址 ' . $ip);
        if(empty($ip)){
            return $this->setError(false, '非法的IP地址访问');
        }
        $isAccess = BlackWhiteList::where(['ip' => $ip])->find();
        if (!$isAccess) {
            return $this->setError(false, 'IP地址不在白名单中');
        }
        //如果是代付，判断代付订单是否重复
        $cash_row = MerchantWithdrawCash::get([
            'mch_id' => $params['mch_id'],
            'mch_order_no' => $params['order_sn']
        ]);
        if ($cash_row) {
            return $this->setError(false, '渠道代付订单号重复');
        }

        $account = MerchantAccount::get([
            'mch_id' => $params['mch_id'],
            'channel' => $params['channel']
        ]);
        if (empty($account)) {
            return $this->setError(false, '商户账户不存在');
        }
        //查询手续费
        $pay_channel_config = PayChannelConfig::get(['class_namespace' => $account->channel]);
        if ($params['acc_attr'] == 2) {
            //对私
            $total_fee = bcadd($params['amount'], $pay_channel_config->cash_poundage, 2);
        } elseif ($params['acc_attr'] == 1) {
            //对公
            $total_fee = bcadd($params['amount'], $pay_channel_config->cash_public_poundage, 2);
        } else {
            return $this->setError(false, '账户属性错误');
        }

        if (!in_array($pay_channel_config->cash_type, [1, 2])) {
            return $this->setError(false, '提现方式配置错误');
        }

        if ($total_fee > $account->balance) {
            return $this->setError(false, '提现余额不足（提现金额 + 手续费）');
        }

        // 每个渠道的最小提现金额限制
        $merchantPayment = MerchantPayment::where([
            'merchant_id' => $params['mch_id'],
            'channel_config_ids' => $pay_channel_config['id']
        ])->find();
        $channelMinCash = $merchantPayment->min_cash;
        if (empty($channelMinCash)) {
            $channelMinCash = 3;
        }
        Log::debug('[新提现] 每个渠道的最小提现金额限制 ' . $channelMinCash);
        if ($params['amount'] < $channelMinCash) {
            return $this->setError(false, '最低提现金额为 ' . $channelMinCash . ' 元');
        }
        if ($this->systemConfig['CASH_MINI_MONEY'] > $params['amount']) {
            return $this->setError(false, '最低提现金额为 ' . $this->systemConfig['CASH_MINI_MONEY'] . ' 元');
        }

        // 需要银行编码的额外处理
        if (in_array($params['channel'], config('cash')['province'])) {
            //汇付宝银行信息
            $bank_info = ChannelBank::where([
                'channel' => $params['channel'],
                'bank_name' => $params['acc_bank']
            ])->find();

            if (empty($bank_info)) {
                return $this->setError(false, $params['channel'] . '银行信息找不到');
            }
            $acc_bank = $bank_info->bank_name;
            $acc_bank_code = $bank_info->bank_code;

            //汇付宝的取出专属城市
            if (in_array($params['channel'], config('cash')['city'])) {
                // 城市
                $cityWhere = [
                    'channel' => $params['channel'],
                    'city_name' => $params['acc_city']
                ];
                $city_info = BankCity::where($cityWhere)
                    ->where('tid', '<>', 0)
                    ->find();
                if (empty($city_info)) {
                    return $this->setError(false, $params['channel'] . '银行所在城市信息找不到');
                }
                Log::debug('[新提现] 城市信息 ' . json_encode($city_info));
                $acc_city = $city_info->city_name;

                // 省份
                $province_info = BankCity::where(['id' => $city_info->tid])->find();
                if (empty($province_info)) {
                    return $this->setError(false, $params['channel'] . '银行所在省份信息找不到');
                }
                $acc_province = $province_info->city_name;

                //和壹付平台代付 省/城市编码
                if (in_array($params['channel'], config('cash')['city_code'])) {
                    $acc_city = $city_info->city_code;
                    $acc_province = $province_info->city_code;
                }
            } else {
                $acc_city = $params['acc_city'];
                $acc_province = $params['acc_province'];
            }
        } else {
            //其他渠道
            $acc_bank = $params['acc_bank'];
            $acc_bank_code = '';
            $acc_city = $params['acc_city'];
            $acc_province = $params['acc_province'];
        }

        $order_no = 'C' . $params['mch_id'] . date('ymdHis') . rand(0, 9);

        $data = [
            'acc_attr' => $params['acc_attr'],
            'acc_bankno' => $params['acc_bank'],
            'acc_bank' => $acc_bank,
            'acc_bank_code' => $acc_bank_code,
            'acc_card' => $params['acc_card'],
            'acc_province' => $acc_province,
            'acc_city' => $acc_city,
            'acc_name' => $params['acc_name'],
            'acc_subbranch' => $params['acc_subbranch'],
            'acc_idcard' => $params['acc_idcard'],
            'acc_mobile' => $params['acc_mobile'],
            'amount' => bcadd($params['amount'], 0, 2),
            'order_no' => $order_no,
            'type' => 1
        ];
        if (empty($this->channelClass[$account->channel])) {
            return $this->setError(false, '平台方错误，找不到渠道配置');
        }
        $result['channel_order_no'] = '';
        $data['mch_order_no'] = $params['order_sn'];
        $data['mch_id'] = $params['mch_id'];
        $data['total_fee'] = $total_fee;
        $data['channel'] = $account->channel;
        $data['channel_order_no'] = $result['channel_order_no'];
        $data['created_at'] = time();
        $data['updated_at'] = time();

        Log::debug('[新提现] 总数据请求：' . json_encode($params));
        $object = App::invokeClass($this->channelClass[$params['channel']]);
        $_result = $object->setMchChannelConfig($params['mch_id']);
        if (!$_result) {
            $error = $object->getReturnMsg();
            Log::error('[新提现] 设置渠道错误信息 : ' . json_encode($error));
            return $this->setError(false, $error['message'], $error['code']);
        }

        try {
            Db::startTrans();
            $merchant_account = Db::name('merchant_account')
                ->lock(true)
                ->find($account->id);
            if ($pay_channel_config->cash_type == 1) {
                // 接口自动提现
                $result = $object->cash($data);
                if (!$result) {
                    $error = $object->getReturnMsg();
                    Log::error('[新提现] 渠道自动提现返回错误信息: ' . json_encode($error));
                    return $this->setError(false, $error['message'], $error['code']);
                }

                $data['channel_order_no'] = $result['channel_order_no'];
                $data['status'] = 1;
                $data['remark'] = $result['message'] ?? '提现成功';
                $data['channel_mch_id'] = $object->mchChannelConfig->channel_mch_id;
                if (isset($result['create_time'])) $data['created_at'] = $result['create_time'];
            } else {
                $data['channel_order_no'] = '';
                $data['status'] = 0;
                $data['remark'] = '提现失败';
                $data['channel_mch_id'] = $object->mchChannelConfig->channel_mch_id;
            }
            Log::debug('[新提现] 总数据请求4：' . json_encode($data));

            //增加提现订单
            Db::name('merchant_withdraw_cash')->insert($data);
            Log::debug('[新提现] 总数据请求5：' . json_encode($merchant_account));
            // 扣除账户余额，增加提现中金额
            $balance = $merchant_account['balance'];
            $merchant_account_update = [
                'balance' => $balance - $total_fee,
                'withdraw_cash_balance' => $merchant_account['withdraw_cash_balance'] - $total_fee,
                'withdraw_cash_ing' => $merchant_account['withdraw_cash_ing'] + $data['amount']
            ];
            Log::debug('[新提现] 扣除账户余额，增加提现中金额' . json_encode($merchant_account_update));
            Db::name('merchant_account')
                ->where(['id' => $account->id])
                ->update($merchant_account_update);

            //增加账户余额明细
            Db::name('merchant_balance_record')->insert([
                'mch_id' => $params['mch_id'],
                'type' => 2,
                'money' => $total_fee,
                'channel' => $account->channel,
                'befor_money' => $balance,
                'after_money' => $merchant_account_update['balance'],
                'remark' => '订单[' . $order_no . ']申请提现扣除' . $data['amount'] . '元, 手续费' . $pay_channel_config->cash_poundage . '元',
                'created_at' => time()
            ]);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            Log::error('[新提现] 提现发生系统异常 ' . $e->getMessage().'|'.$e->getTraceAsString().'|'.$e->getLine());
            return $this->setError(false, '系统异常，请稍后再试', 500);
        }
        Log::debug('[新提现] 事务提交成功');
        return [
            'order_no' => $data['order_no'],
            'channel_order_no' => $data['channel_order_no'],
        ];
    }

    /**
     * 账户余额查询
     * @param $channel
     * @return mixed
     */
    public function balance($channel, $mch_id)
    {

        if (empty($this->channelClass[$channel])) {
            return $this->setError(false, '平台方错误，找不到渠道配置');
        }
        $object = App::invokeClass($this->channelClass[$channel]);
        //设置商户渠道配置
        $_result = $object->setMchChannelConfig($mch_id);
        if (!$_result) {
            $error = $object->getReturnMsg();
            return $this->setError(false, $error['message']);
        }
        $result = $object->balance([]);
        if (!$result) {
            $error = $object->getReturnMsg();
            return $this->setError(false, $error['message'], $error['code'], $error['data']);
        }

        return $result;
    }

    /**
     * 同步返回
     */
    public function returnUrl()
    {
        $data = request()->param();
        $channel_str = '';

        $channel_str = ''; //渠道标识
        $mch_order_no = ''; //渠道订单号
        $mch_id = ''; //商户号

        Log::debug('同步通知=》' . json_encode($data));

        //区分渠道
        if (isset($data['fl_order_no'])) {
            //摇钱树
            $channel_str = 'yaoqianshu';
            $mch_order_no = $data['order_no'];
        } elseif (isset($data['trade_no'])) {
            //杉德
            $mch_order_no = $data['out_trade_no'];
            //区分一个渠道开了多个渠道问题，取订单中的渠道标识
            $order = Order::get(['order_no' => $mch_order_no]);
            if (empty($order)) {
                return $this->setError(false, '订单不存在');
            }
            $channel_str = $order->channel;

        } elseif (isset($data['jnet_bill_no']) || (isset($data['agent_id']) && isset($data['encrypt_data']))) {
            //汇付宝
            $channel_str = 'heepay';
            if (!empty($data['jnet_bill_no'])) {
                $mch_order_no = $data['agent_bill_id'];
                $order = Order::get(['order_no' => $mch_order_no]);
                if (empty($order)) {
                    return $this->setError(false, '订单不存在11111');
                }
                $channel_str = $order->channel;
            } elseif (!empty($data['agent_id']) && !empty($data['encrypt_data'])) {
                //汇付宝快捷支付，查询商户渠道配置
                $mch_channel_config = MerchantChannelConfig::alias('a')
                    ->where(['a.channel' => 'heepay', 'b.channel_mch_id' => $data['agent_id']])
                    ->join('channel_merchant b', 'a.channel_mch_id = b.id', 'left')
                    ->find();
                $mch_id = $mch_channel_config->mch_id;
            }
        } elseif (isset($data['outOrderId'])) {
            //商银信
            $channel_str = 'allscore';
            $mch_order_no = $data['outOrderId'];
        } elseif (isset($data['paymentResult'])) {
            $channel_str = 'ips';
            $xmlResult = new SimpleXMLElement($data['paymentResult']);
            $mch_order_no = $xmlResult->GateWayRsp->body->MerBillNo;
        } elseif (isset($data['reCode']) && isset($data['result'])) {
            // 和壹付平台支付同步
            $channel_str = 'saas';
            $mch_order_no = $data['trxMerchantOrderno'];
        } elseif (isset($data['charset']) && isset($data['data'])) {
            //杉德总部
            $channel_str = 'sandpay';
            $de_data = json_decode($data['data'], true);
            $mch_order_no = $de_data['body']['orderCode'];
        } elseif (isset($data['sdorderno']) && isset($data['sdpayno']) && isset($data['sdpayno1'])) {
            // 国华汇银
            Log::debug('[同步返回] 国华汇银 ' . json_encode($data));
            $channel_str = 'unionchinapay';
            $mch_order_no = $data['sdpayno'];
        } elseif (isset($data['sysorderid']) && isset($data['orderid'])) {
            // 裕支付
            Log::debug('[同步返回] 裕支付 ' . json_encode($data));
            $channel_str = 'yycshop';
            $mch_order_no = $data['orderid'];
        }elseif(isset($data['customerid'])){
            //易通付
            //{"status":"1","customerid":"11149","sdpayno":"2018092618081414868","sdorderno":"S120011809261808137125","total_fee":"0.01","paytype":"aliwap","remark":"","sign":"39715f26413e768ec49e3458f4328d4a"}
            Log::debug('[异步通知] 易通付 ' . json_encode($data));
            $mch_order_no = $data['sdorderno'];
        } else{
            $ip = request()->ip();
            $getRemoteIp = RedisLock::getRemoteIp();
            Log::debug('[同步返回] 开联通$ip ' . $ip);
            Log::debug('[同步返回] 开联通$getRemoteIp ' . $getRemoteIp);
        }

        if (empty($channel_str)) {
            return $this->setError(false, '非法访问');
        }
        if (empty($mch_id)) {
            //如果没有商家ID，则通过订单去查询商家id
            $order = Order::get(['order_no' => $mch_order_no]);
            if (empty($order)) {
                return $this->setError(false, '订单不存在1');
            }
            $mch_id = $order->mch_id;
        }

        $object = App::invokeClass(config('channel_class')[$channel_str]);
        //设置商户渠道配置
        $_result = $object->setMchChannelConfig($mch_id);
        if (!$_result) {
            $error = $object->getReturnMsg();
            return $this->setError(false, $error['message']);
        }
        $result = $object->notify($data);
        if (!$result) {
            $error = $object->getReturnMsg();
            return $this->setError(false, '平台方错误=》' . $error['message'], $error['code']);
        }
        //先将字符串转大写，避免有些渠道自动转小写匹配不到订单问题
        $result['order_no'] = strtoupper($result['order_no']);
        //区分订单类型 SN_支付订单   CS_提现订单
        $order_no = $result['order_no'];


        $order = Order::get(['order_no' => $order_no]);
        if (empty($order)) {
            return $this->setError(false, '订单不存在2');
        }

        //如果同步跳转笔异步通知早，则在此查询订单并更新订单状态
        if ($order->status == 0 && in_array($order->channel, ['ips', 'xtenpay', 'xtenpay_quick', 'sandpay', 'unionchinapay', 'chemstar'])) {
            $type = mb_substr($order_no, 0, 1, 'utf-8');
            if ($type == 'S') {
                //查询订单状态
                $_req = $object->orderQuery([
                    'order_no' => $order->order_no,
                    'create_time' => $order->create_time,
                    'total_fee' => $order->total_fee
                ]);
                Log::debug('[订单查询结果] ' . json_encode($_req));
                if (!$_req) {
                    $error = $object->getReturnMsg();
                    return $this->setError(false, $error['message'], $error['code']);
                }
                $pay_result = [
                    'total_fee' => $_req['total_fee'],
                    'channel_order_no' => $_req['channel_order_no'],
                    'order_no' => $_req['order_no']
                ];
                if ($_req['status'] == 1) {
                    $pay_result['status'] = 'success';
                } elseif ($_req['status'] == -1) {
                    $pay_result['status'] = 'fail';
                } elseif ($_req['status'] == 0) {
                    $pay_result['status'] = 'wait';
                } else {
                    return $this->setError(false, '订单状态错误');
                }
                if ($order->jiesuan_clear_type != 0) {
                    $this->payNotify($order->channel, $pay_result);
                } else {
                    $this->payNotifyOld($order->channel, $pay_result);
                }

                //重新查询更新后的订单信息
                $order = Order::get(['order_no' => $order_no]);
            }
        }

        $mch = Merchant::get($order->mch_id);

        if (!empty($order->pay_time)) {
            $pay_time = date('Y-m-d H:i:s', $order->pay_time);
        } else {
            $pay_time = '';
        }
        $send_data = [
            'order_no' => (string)$order->order_no,
            'mch_order_no' => (string)$order->mch_order_no,
            'goods' => (string)$order->goods,
            'total_fee' => (float)$order->total_fee,
            'payment' => (string)$order->payment,
            'rate' => (float)$order->rate,
            'service_charge' => (float)$order->service_charge,
            'status' => (int)$order->status,
            'create_time' => date('Y-m-d H:i:s', $order->create_time),
            'pay_time' => $pay_time,
        ];

        $sign = $this->sign($send_data, $mch);
        $send_data['sign'] = $sign;

        Log::info('跳转同步网址：(' . $order->return_url . ')' . json_encode($send_data));

        $url = '';
        if (!empty($order->return_url)) {
            $url = $order->return_url . '?' . http_build_query($send_data);
        }

        return array_merge($send_data, ['go_url' => $url]);
    }

    /**
     * 提现查询
     * @param array $channel
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function cashQuery($channel = ['xtenpay', 'xtenpay_quick'])
    {
        //查询杉德提现中的订单
        $cash_order = MerchantWithdrawCash::where([
            'status' => 1,
            'channel' => ['in', $channel]
        ])->order('id', 'asc')->limit(5)->select();

        foreach ($cash_order as $item) {

            if ($item->fail_count >= 2) {
                //失败次数大于2，自动列为异常订单
                $item->update(['status' => 4]);
                continue;
            }

            $mch_chanel_config = MerchantChannelConfig::where(['mch_id' => $item->mch_id, 'channel' => $item->channel])->find();
            if (empty($mch_chanel_config)) {
                $item->update([
                    'fail_count' => $item->fail_count + 1,
                    'remark' => '商户渠道商户号配置错误'
                ]);
                //成功或失败都要继续循环
                continue;
            }
            $object = App::invokeClass(config('channel_class')[$item->channel]);
            //设置商户渠道配置
            $_result = $object->setMchChannelConfig($item->mch_id, $item->channel_mch_id);
            if (!$_result) {
                $error = $object->getReturnMsg();
                $item->update([
                    'fail_count' => $item->fail_count + 1,
                    'remark' => $error['message']
                ]);
                //成功或失败都要继续循环
                continue;
            }
            $option = [
                'order_no' => $item->order_no,
                'channel_mch_account' => $item->channel_mch_account,
                'created_at' => $item->created_at,
                'acc_attr' => $item->acc_attr,
                'amount' => $item->amount
            ];

            $result = $object->cashQuery($option, $item->channel_order_no);
            Log::debug('提现查询处理结果=》' . json_encode($result));
            if (!$result) {
                $error = $object->getReturnMsg();
                $item->update([
                    'fail_count' => $item->fail_count + 1,
                    'remark' => $error['message']
                ]);
                continue;
            }

            $_status = '';
            //成功查询到订单状态
            if ($result['status'] == 0) {
                //处理中
                continue;
            } elseif ($result['status'] == 1) {
                //处理成功
                $_status = 'success';
            } elseif ($result['status'] == 2) {
                //处理失败
                $_status = 'fail';
            } elseif ($result['status'] == 3) {
                //处理中
                continue;
            }

            if (!empty($_status)) {
                $result = [
                    'status' => $_status,
                    'order_no' => $result['order_no'],
                    'channel_order_no' => $result['channel_order_no'],
                    'total_fee' => $result['total_fee'],
                    'remark' => $result['remark'],
                ];
                $_res = $this->cashNotify($item->channel, $result);
                if (!$_res) {
                    $item->update([
                        'fail_count' => $item->fail_count + 1,
                        'remark' => $this->getError()['message']
                    ]);
                }
                //成功或失败都要继续循环
                continue;
            }
        }
    }

    /**
     * 代付
     */
    public function agentPay($mch_id, $data, $version)
    {

        if (empty($data['amount']) || $data['amount'] <= 0) {
            return $this->setError(false, '提现金额必须大于0', -1);
        }

        //自主提现，获取银行卡信息
//            ['channel', 'acc_bank', 'acc_bankno', 'acc_city', 'acc_attr', 'acc_name', 'acc_card', 'acc_idcard', 'acc_mobile', 'acc_subbranch']
        $validate = new Validate([
            'channel_code' => 'require',
            'mch_order_no' => 'require',
            'acc_bank' => 'require',
            'acc_province' => 'require',
            'acc_city' => 'require',
            'acc_attr' => 'require',
            'acc_card' => 'require',
            'acc_bankno' => 'require',
            'acc_subbranch' => 'require',
            'acc_name' => 'require',
            'acc_idcard' => 'require',
            'acc_mobile' => 'require',
        ], [], [
            'channel_code' => '渠道',
            'mch_order_no' => '代付订单号',
            'acc_bank' => '银行简码',
            'acc_bankno' => '开户行联行号',
            'acc_province' => '省份',
            'acc_city' => '城市',
            'acc_attr' => '卡类型',
            'acc_name' => '持卡人姓名',
            'acc_card' => '银行卡号',
            'acc_idcard' => '身份证号',
            'acc_mobile' => '银行预留手机号',
            'acc_subbranch' => '支行名称',
        ]);
        if (!$validate->check($data)) {
            return $this->setError(false, $validate->getError(), -1);
        }
        $channel_code_array = [
            1 => 'yaoqianshu',
            2 => 'xtenpay',
            3 => 'xtenpay_quick',
            4 => 'heepay',
            5 => 'allscore',
        ];
        if (!isset($channel_code_array[$data['channel_code']])) {
            return $this->setError(false, '未知通道', -1);
        }
        if ($data['channel_code'] != 5) {
            return $this->setError(false, '所选通道不支持代付', -1);
        }

//        银行处理
        if (in_array($channel_code_array[$data['channel_code']], ['heepay', 'allscore'])) {
            $bank_info = \app\common\model\ChannelBank::get(['channel' => $channel_code_array[$data['channel_code']], 'bank_code' => $data['acc_bank']]);
            if (empty($bank_info)) {
                return $this->setError(false, '银行简码错误', -1);
            }
            $data['acc_bank'] = $bank_info->id;
        }
        //城市处理 TODO==
//        if ($channel_code_array[$data['channel_code']] == 'heepay'){
//
//        }

        $card = json_decode(json_encode([
            'channel' => $channel_code_array[$data['channel_code']],
            'mch_order_no' => $data['mch_order_no'],
            'acc_bank' => $data['acc_bank'],
            'acc_bankno' => $data['acc_bankno'],
            'acc_province' => $data['acc_province'],
            'acc_city' => $data['acc_city'],
            'acc_attr' => $data['acc_attr'],
            'acc_name' => $data['acc_name'],
            'acc_card' => $data['acc_card'],
            'acc_idcard' => $data['acc_idcard'],
            'acc_mobile' => $data['acc_mobile'],
            'acc_subbranch' => $data['acc_subbranch'],
        ]));
        $account = MerchantAccount::get(['mch_id' => $mch_id, 'channel' => $channel_code_array[$data['channel_code']]]);
        if (empty($account)) {
            return $this->setError(false, '渠道账户不存在，请联系管理员', -1);
        }

        $result = $this->cash($mch_id, $data['amount'], $card, $account->id, 2);

        if (!$result) {
            $error = $this->getError();
            return $this->setError(false, $error['message'], $error['code']);
        }

        return [
            'order_no' => $result['order_no'],
            'mch_order_no' => $card->mch_order_no
        ];
    }

    /**
     * 代付查询
     * @param $mch_id
     * @param $data
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function agentPayQuery($mch_id, $data, $version)
    {
        if (!isset($data['mch_order_no']) || !is_string($data['mch_order_no'])) {
            return $this->setError(false, '参数错误', -1);
        }
        $mch_order_no_array = explode(',', trim($data['mch_order_no'], ','));
        $mch_order_no_array_result = [];
        $count = 0;
        foreach ($mch_order_no_array as $item) {
            if ($item == '') {
                return $this->setError(false, '参数错误', -1);
            }
            if (in_array($item, $mch_order_no_array_result)) {
                return $this->setError(false, '代付单号重复', -1);
            }
            $mch_order_no_array_result[] = $item;
            $count++;
        }

        if ($count > 50) {
            return $this->setError(false, '单词查询不能多于50条', -1);
        }

        $cash_list = MerchantWithdrawCash::where(['mch_id' => $mch_id, 'type' => 2])->whereIn('mch_order_no', $mch_order_no_array_result)->select();
        if (empty($cash_list)) {
            return $this->setError(false, '未找到代付记录', -1);
        }

        $result_list = [];
        foreach ($cash_list as $item) {
            $_data = [
                'mch_order_no' => (string)$item->mch_order_no,
                'order_no' => (string)$item->order_no,
                'acc_card' => (string)$item->acc_card,
                'amount' => (float)$item->amount,
                'remark' => (string)$item->remark,
                'poundage' => (float)$item->total_fee - $item->amount
            ];
//            状态 0待审核  1提现中  2提现成功  3提现失败  4异常订单
            $status = '01';
            if ($item->status == 0) {
                $status = '00';
            } elseif ($item->status == 1) {
                $status = '01';
            } elseif ($item->status == 2) {
                $status = '02';
            } elseif ($item->status == 3) {
                $status = '03';
            } elseif ($item->status == 4) {
                $status = '04';
            }
            $_data['status'] = (string)$status;

            $result_list[] = $_data;
        }

        return $result_list;
    }

    /**
     * 环讯商户提现
     * 绑卡提现用于必须绑卡的渠道提现（环讯）
     */
    public function ipsCash($mch_id, $amount, $user_account_id)
    {

        //查询账号信息
        $channel_mch_account = ChannelMerchantAccount::get($user_account_id);
        if (empty($channel_mch_account)) {
            return $this->setError(false, '子账户不存在');
        }
        $account = \app\common\model\MerchantAccount::get([
            'mch_id' => $mch_id,
            'channel' => $channel_mch_account->channel
        ]);
        if (empty($account)) {
            return $this->setError(false, '渠道账户不存在');
        }
        if (!in_array($account->channel, ['ips'])) {
            return $this->setError(false, '渠道类型错误');
        }

        $ips = new Ips();
        $_result = $ips->setMchChannelConfig($mch_id);
        if (!$_result) {
            $error = $ips->getReturnMsg();
            return $this->setError(false, $error['message'], $error['code']);
        }
        //查询子账户认证状态
        $auth_status = $ips->authStatusQuery([
            'customer_code' => $channel_mch_account->account
        ]);
        if (!$auth_status) {
            $error = $ips->getReturnMsg();
            return $this->setError(false, $error['message'], $error['code']);
        }
        if ($auth_status['channel_count'] < 3) {
            return $this->setError(false, '当前账户状态不支持提现');
        }
        if ($auth_status['auth_status'] == 4) {
            return $this->setError(false, '当前账户二次认证审核中，不支持提现');
        }

        //查询手续费
        $pay_channel_config = PayChannelConfig::get(['class_namespace' => $account->channel]);
        $total_fee = bcadd($amount, $pay_channel_config->cash_poundage, 2);

        if ($auth_status['auth_status'] != 5 && $total_fee > 10000) {
            return $this->setError(false, '未影印件认证用户单笔提现限额 1w');
        }

        if ($this->systemConfig['CASH_MINI_MONEY'] > $amount) {
            return $this->setError(false, '最低提现金额为 ' . $this->systemConfig['CASH_MINI_MONEY'] . ' 元');
        }

        //查询卡信息
        $card = $ips->cardQuery([
            'customer_code' => $channel_mch_account->account
        ]);
        if (!$card) {
            $error = $ips->getReturnMsg();
            return $this->setError(false, $error['message']);
        }

        if ($total_fee > $channel_mch_account->balance) {
            //余额不足
            //查询商户余额是否够
            if (($account->withdraw_cash_balance + $channel_mch_account->balance) < $total_fee) {
                return $this->setError(false, '可提现余额不足');
            }
            //商户余额够，用户账户余额不足，则商户向用户账户转账
            $transfer_total_fee = bcsub($total_fee, $channel_mch_account->balance, 2); //待转账金额
            //转账
            $req = $this->ipsTranfer($transfer_total_fee, $account->id, $user_account_id);
            if (!$req) {
                return false;
            }
            $channel_mch_account = ChannelMerchantAccount::get($user_account_id);
        }

        //查询渠道账户余额
        $balance = $ips->balance([
            'customer_code' => $channel_mch_account->account
        ]);
        if (!$balance) {
            $error = $balance->getReturnMsg();
            return $this->setError(false, $error['message']);
        }
        if ($balance['usable'] < $total_fee) {
            return $this->setError(false, '账户余额不足');
        }

        $order_no = 'C' . $mch_id . date('ymdHis') . rand(0, 9);

        $data = [
            'acc_attr' => 2,
            'acc_bankno' => '',
            'acc_bank' => $card['bank_name'],
            'acc_bank_code' => '',
            'acc_card' => $card['bank_card'],
            'acc_province' => '',
            'acc_city' => '',
            'acc_name' => $channel_mch_account->username,
            'acc_subbranch' => '',
            'acc_idcard' => $channel_mch_account->identity_no,
            'acc_mobile' => $channel_mch_account->mobie_phone_no,
            'amount' => $amount,
            'order_no' => $order_no,
            'channel_mch_id' => $ips->mchChannelConfig->channel_mch_id,
            'channel_mch_account' => $channel_mch_account->account,
        ];
        $result['channel_order_no'] = '';

        $data['mch_id'] = $mch_id;
        $data['total_fee'] = $total_fee;
        $data['channel'] = $account->channel;
        $data['channel_order_no'] = $result['channel_order_no'];
        $data['created_at'] = time();
        $data['updated_at'] = time();

        Db::startTrans();
        $channel_account = Db::name('channel_merchant_account')->lock(true)->find($user_account_id);
        $merchant_account = Db::name('merchant_account')->lock(true)->find($account->id);

        try {
            //请求接口提现
            $result = $ips->cash(array_merge($data, [
                'customer_code' => $channel_mch_account->account
            ]));
            if (!$result) {
                $error = $ips->getReturnMsg();
                Log::error('平台方错误=》' . $error['message']);
                return $this->setError(false, $error['message'], $error['code']);
            }
            $data['channel_order_no'] = $result['channel_order_no'];
            $data['status'] = 1;

            //增加提现订单
            Db::name('merchant_withdraw_cash')->insert($data);

            //扣除渠道账户客户号余额
            Db::name('channel_merchant_account')->where(['id' => $user_account_id])->update([
                'balance' => $channel_account['balance'] - $total_fee
            ]);

            //增加提现中金额
            Db::name('merchant_account')->where(['id' => $merchant_account['id']])->update([
                'withdraw_cash_ing' => $merchant_account['withdraw_cash_ing'] + $data['amount']
            ]);

            Db::commit();

            return [
                'order_no' => $data['order_no'],
                'channel_order_no' => $data['channel_order_no'],
            ];

        } catch (\Exception $e) {
            Db::rollback();

            Log::error('提现提价交败=》' . $e->getTraceAsString());
            return $this->setError(false, '系统异常，请稍后再试', 500);
        }
    }

    /**
     * 环讯商户向用户转账
     * @param $total_fee
     * @param $merchant_account_id
     * @param $channel_account_id
     * @return bool|mixed
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    private function ipsTranfer($total_fee, $merchant_account_id, $channel_account_id)
    {

        Db::startTrans();

        $merchant_account = Db::name('merchant_account')->lock(true)->find($merchant_account_id);
        $channel_account = Db::name('channel_merchant_account')->lock(true)->find($channel_account_id);

        try {
            $ips = new Ips();
            $req = $ips->setMchChannelConfig($merchant_account['mch_id']);
            if (!$req) {
                return $this->setError(false, $ips->getReturnMsg()['message']);
            }
            $result = $ips->transfer([
                'customer_code' => $channel_account['account'], //渠道商户客户号
                'transfer_amount' => $total_fee, //转账金额
                'collection_item_name' => '入金', //付款项目
                'remark' => '入金'
            ]);
            if (!$result) {
                $error = $ips->getReturnMsg();
                Log::error('转账失败=》' . json_encode($error));
                return $this->setError(false, $error['message']);
            }
            //扣除余额
            $balance = $merchant_account['balance'];
            $merchant_account_update = [
                'balance' => round(bcsub($balance, $total_fee, 3), 2),
                'withdraw_cash_balance' => round(bcsub($merchant_account['withdraw_cash_balance'], $total_fee, 3), 2),
                'channel_transfer_money' => bcadd($merchant_account['channel_transfer_money'], $total_fee, 3)
            ];
            Db::name('merchant_account')->where(['id' => $merchant_account_id])->update($merchant_account_update);

            //增加渠道商户客户号余额
            Db::name('channel_merchant_account')->where(['id' => $channel_account_id])->update([
                'balance' => $channel_account['balance'] + $total_fee
            ]);

            //增加账户余额明细
            Db::name('merchant_balance_record')->insert([
                'mch_id' => $merchant_account['mch_id'],
                'type' => 2,
                'money' => $total_fee,
                'channel' => 'ips',
                'befor_money' => $balance,
                'after_money' => $merchant_account_update['balance'],
                'remark' => '[' . $ips->mchChannelConfig->channel_mch_id . ']转账到子账户[' . $channel_account['account'] . ']扣除' . $total_fee . '元',
                'created_at' => time()
            ]);

            Db::commit();
            Log::info('转账成功(' . json_encode($req) . ')');

            return true;

        } catch (\Exception $exception) {
            Db::rollback();
            Log::error('转账失败(' . $exception->getTraceAsString() . ')');

            return $this->setError(false, '系统异常，请联系客服');
        }
    }

    /**
     * 渠道订单查询
     * @param $mch_id
     * @param array $option
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function channelOrderQuery($mch_id, $option = [])
    {
        if (empty($option['order_no'])) {
            return $this->setError(false, '平台订单号参数错误');
        }
        $order_info = Order::get(['mch_id' => $mch_id, 'order_no' => $option['order_no'], 'channel' => ['neq', '']]);
        if (empty($order_info)) {
            return $this->setError(false, '订单不存在');
        }

        //订单状态不为1，则去渠道方查询
        //查询渠道配置
        $channel_config = PayChannelConfig::get(['class_namespace' => $order_info->channel]);
        if (empty($channel_config)) {
            return $this->setError(false, '平台方错误，渠道配置不存在！');
        }
        if (empty($channel_config->status)) {
            return $this->setError(false, '平台方错误，渠道已关闭！');
        }
        if (empty($this->channelClass[$channel_config->class_namespace])) {
            return $this->setError(false, '平台方错误，渠道标识错误！');
        }

        //实例化渠道类
        $object = App::invokeClass($this->channelClass[$channel_config->class_namespace]);
        //设置商户渠道配置
        $_result = $object->setMchChannelConfig($mch_id, $order_info->channel_merchant_no);
        if (!$_result) {
            $error = $object->getReturnMsg();
            return $this->setError(false, $error['message']);
        }

        $order = json_decode(json_encode($order_info), true);
        $req = $object->orderQuery($order);
        if (!$req) {
            $error = $object->getReturnMsg();
            return $this->setError(false, $error['message']);
        }

        return [
            'order_no' => (string)$req['order_no'],
            'mch_order_no' => (string)$order_info->mch_order_no,
            'total_fee' => (float)$order_info->total_fee,//金额
            'create_date' => (string)date('Y-m-d H:i:s', $order_info->create_time), //创建时间
            'status' => (int)$req['status']
        ];

    }

    /**
     * 环讯创建用户成功处理
     * @param $channel_merchant_account_id
     * @param array $request_data
     * @return bool|mixed
     */
    public function ipsCreateUser($channel_merchant_account_id, $request_data = [])
    {

        Db::startTrans();
        try {
            //渠道商户客户号记录
            $channel_merchant_account = Db::name('channel_merchant_account')->where(['id' => $channel_merchant_account_id])->find();

            $channel = $channel_merchant_account['channel'];
            $mch_id = $channel_merchant_account['mch_id'];
            $channel_mch_id = $channel_merchant_account['channel_mch_id'];
            $channel_mch_account = $channel_merchant_account['account'];

            //商户账户
            $mch_account = Db::name('merchant_account')->where(['channel' => $channel, 'mch_id' => $mch_id])->lock(true)->find();
            //三方商户号账户
            $channel_merchant_balance = Db::name('channel_merchant_balance')->where(['channel_merchant' => $channel_mch_id, 'channel' => $channel])->lock(true)->find();
            //开户费
            $money = Db::name('system_config')->where(['config_name' => 'IPS_CREATE_USER_MONEY'])->value('config_value');
            //成本开户费
            $base_money = Db::name('pay_channel_config')->where(['class_namespace' => $channel])->value('create_user_money_base');

            //更新渠道商户客户号
            Db::name('channel_merchant_account')->where(['id' => $channel_merchant_account_id])->update(['status' => 2, 'request_data' => json_encode($request_data)]);

            //扣除开户费
            Db::name('merchant_account')->where(['id' => $mch_account['id']])->update([
                'balance' => ['exp', 'balance - ' . $money], //余额
                'withdraw_cash_balance' => ['exp', 'withdraw_cash_balance - ' . $money], //可提现
            ]);
            //增加商户账户余额明细
            Db::name('merchant_balance_record')->insert([
                'mch_id' => $mch_id,
                'type' => 2,
                'channel' => $channel,
                'money' => $money,
                'befor_money' => $mch_account['balance'],
                'after_money' => $mch_account['balance'] - $money,
                'remark' => '客户[' . $channel_mch_account . ']开户扣除' . $money . '元',
                'created_at' => time()
            ]);
            //三方商户号利润处理
            if ($mch_account['withdraw_cash_balance'] < $base_money) {
                //亏本
                $_money = bcsub($base_money, $mch_account['withdraw_cash_balance'], 4);
                //扣除三方商户号余额
                Db::name('channel_merchant_balance')
                    ->where(['id' => $channel_merchant_balance['id']])
                    ->update([
                        'balance' => ['exp', 'balance - ' . $_money], //余额
                        'withdraw_cash_balance' => ['exp', 'withdraw_cash_balance - ' . $_money]
                    ]);
                //插入资金记录
                Db::name('channel_merchant_balance_record')
                    ->insert([
                        'channel_merchant' => $channel_mch_id,
                        'channel' => $channel,
                        'record_type' => 2,
                        'type' => 2,
                        'money' => $_money,
                        'after_money' => bcsub($channel_merchant_balance['balance'], $_money, 4),
                        'befor_money' => $channel_merchant_balance['balance'],
                        'remark' => '客户[' . $channel_mch_account . ']开户亏本' . $_money . '元',
                        'created_at' => time(),
                    ]);

            } else {
                //盈利
                $_money = bcsub($money, $base_money, 4);
                //增加三方商户号余额
                Db::name('channel_merchant_balance')
                    ->where(['id' => $channel_merchant_balance['id']])
                    ->update([
                        'balance' => ['exp', 'balance + ' . $_money], //余额
                        'withdraw_cash_balance' => ['exp', 'withdraw_cash_balance + ' . $_money]
                    ]);
                //插入资金记录
                Db::name('channel_merchant_balance_record')
                    ->insert([
                        'channel_merchant' => $channel_mch_id,
                        'channel' => $channel,
                        'record_type' => 2,
                        'type' => 1,
                        'money' => $_money,
                        'after_money' => bcadd($channel_merchant_balance['balance'], $_money, 4),
                        'befor_money' => $channel_merchant_balance['balance'],
                        'remark' => '客户[' . $channel_mch_account . ']开户增加' . $_money . '元',
                        'created_at' => time(),
                    ]);
            }

            Db::commit();

            return true;

        } catch (\Exception $e) {
            Db::rollback();
            Log::error('ips开户成功，系统处理失败(' . $e->getMessage() . ')=>' . $e->getTraceAsString());

            return $this->setError(false, '系统异常（' . $e->getMessage() . '）');
        }
    }

    /**
     * 订单查询
     * @param $params
     * @param int $version
     */
    public function orderQuery($params, $version = 1)
    {
//        if
    }

}