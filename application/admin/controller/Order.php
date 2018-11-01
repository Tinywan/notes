<?php

namespace app\admin\controller;

use app\common\controller\AdminController;
use think\Db;
use think\Exception;
use think\facade\Log;

class Order extends AdminController {

    use \app\common\traits\controller\Curd;

    public function model(){ return \app\common\model\Order::class; }

    public function init()
    {
        $this->route = 'admin/order';
        $this->label = '订单列表';
        $this->function['create'] = 0;
        $this->function['edit'] = 1;
        $this->function['delete'] = 0;
        $this->where['type'] = 1;

        $this->translations = [
            'id' => ['text' => 'ID'],
            'mch_id' => [
                'text' => '商户',
                'type' => 'join',
                'data' => [
                    'table' => 'merchant',
                    'alias' => 'b',
                    'show_field' => 'username',
                    'value_field' => 'id',
                ]
            ],
            'agents_id' => [
                'text' => '代理商',
                'type' => 'join',
                'data' => [
                    'table' => 'agents',
                    'alias' => 'e',
                    'show_field' => 'agents_name',
                    'value_field' => 'id',
                ]
            ],
            'salesman_id' => [
                'text' => '业务员',
                'type' => 'join',
                'data' => [
                    'table' => 'agents',
                    'alias' => 'f',
                    'show_field' => 'agents_name',
                    'value_field' => 'id',
                ]
            ],
            'cost_service_charge' => ['text' => '成本费'],
            'net_profit' => ['text' => '利润'],
            'agents_rate' => ['text' => '代理费率'],
            'agents_income' => ['text' => '代理收入'],
            'jiesuan_time' => ['text' => '预计结算时间', 'type' => 'time'],
            'fact_jiesuan_time' => ['text' => '实际结算时间', 'type' => 'time'],
            'jiesuan_status' => [
                'text' => '结算状态',
                'type' => 'radio',
                'list' => [
                    0 => ['label label-default', '未结算'],
                    1 => ['label label-info', '已结算'],
                    2 => ['label label-default', '结算失败'],
                ]
            ],
            'order_no' => ['text' => '订单号'],
            'mch_order_no' => ['text' => '商户订单号'],
            'channel_order_no' => ['text' => '渠道订单号'],
            'goods' => ['text' => '商品名'],
            'total_fee' => ['text' => '金额', 'type' => 'number'],
            'return_url' => ['text' => '同步通知地址'],
            'notify_url' => ['text' => '异步通知地址'],
            'channel' => [
                'text' => '渠道',
                'type' => 'join',
                'data' => [
                    'table' => 'pay_channel_config',
                    'alias' => 'c',
                    'show_field' => 'name_remark',
                    'value_field' => 'class_namespace',
                ]
            ],
            'channel_merchant_no' => ['text' => '三方商户号'],
            'payment' => [
                'text' => '支付方式',
                'type' => 'join',
                'data' => [
                    'table' => 'pay_payment',
                    'alias' => 'd',
                    'show_field' => 'payment_name',
                    'value_field' => 'key',
                ]
            ],
            'cost_rate' => ['text' => '成本费率'],
            'rate' => ['text' => '费率'],
            'service_charge' => ['text' => '手续费'],
            'status' => [
                'text' => '支付状态',
                'type' => 'radio',
                'list' => [
                    -1 => ['label label-danger', '支付失败'],
                    0 => ['label label-default', '未支付', true],
                    1 => ['label label-info', '已支付'],
                    2 => ['label label-warning', '已退款'],
                ]

            ],
            'notify_status' => [
                'text' => '通知状态',
                'type' => 'radio',
                'list' => [
                    'no' => ['label label-default', '未通知'],
                    'yes' => ['label label-info', '成功'],
                    'fail' => ['label label-danger', '失败'],
                ]
            ],
            'create_time' => ['text' => '创建时间', 'type' => 'time'],
            'pay_time' => ['text' => '支付时间', 'type' => 'time'],
            'refund_time' => ['text' => '退款时间', 'type' => 'time'],
            'channel_return_data' => ['text' => '渠道返回数据'],
        ];

        $this->listFields = ['mch_id', 'agents_id', 'order_no', 'mch_order_no', 'total_fee', 'channel', 'payment', 'rate', 'service_charge', 'status', 'notify_status', 'jiesuan_status', 'create_time'];

        $this->addFormFields = $this->updateFormFields = ['total_fee', 'rate', 'service_charge', 'status', 'pay_time'];

        $this->searchFields = ['mch_id', 'agents_id', 'channel', 'payment', 'order_no', 'status'];
        $this->searchMoreField = ['mch_order_no',  'channel_merchant_no', 'total_fee', 'create_time'];

        $this->excelFields = ['mch_id', 'agents_id', 'order_no', 'mch_order_no', 'channel_order_no', 'total_fee', 'channel', 'channel_merchant_no', 'payment', 'cost_rate', 'rate', 'service_charge', 'status', 'notify_status', 'create_time', 'pay_time'];

        $this->readFields = ['mch_id', 'agents_id', 'salesman_id', 'order_no', 'mch_order_no', 'channel_order_no', 'total_fee', 'channel', 'channel_merchant_no', 'payment', 'cost_rate', 'cost_service_charge', 'rate', 'service_charge', 'net_profit', 'agents_rate', 'agents_income', 'status', 'notify_status', 'create_time', 'pay_time', 'jiesuan_time', 'jiesuan_status', 'fact_jiesuan_time', 'refund_time', 'notify_url', 'return_url', 'channel_return_data'];
        $this->moreFunction = $this->getMoreFunction();
    }

    public function getDefaultSearch()
    {
        return [
            'create_time' => date('Y/m/d H:i:s', (strtotime(date('Y-m-d', time())))).' - '.date('Y/m/d H:i:s', (strtotime(date('Y-m-d', time())) + (3600 * 24))),
        ];

    }

    public function getMoreFunction()
    {
        return [
            [
                'btn' => 'warning',
                'icon' => 'fa fa-sticky-note-o',
                'text' => '退款',
                'route' => 'admin/order/refundDetail',
                'model_x' => '35%',
                'model_y' => '90%',
                'where' => [
                    'key' => 'status',
                    'value' => '1'
                ]
            ],
        ];
    }

    /**
     * 退款详情
     * @return $this
     * @throws \think\exception\DbException
     */
    public function refundDetail()
    {
        $id = request()->get('ids');
        $orderInfo = OrderModel::get($id);
        return view()->assign([
            'list' => $orderInfo
        ]);
    }

    /**
     * 退款操作
     */
    public function refundOperate()
    {
        $id = request()->post('id');
        $orderInfo = OrderModel::get($id);
        if (empty($orderInfo)) {
            responseJson(false, 0, '该订单不存在');
        }

        if ($orderInfo['status'] != 1) {
            responseJson(false, 0, '该订单未支付不存在退款');
        }

        if (!empty($orderInfo['refund_time'])) {
            responseJson(false, 0, '请不要重复退款');
        }

        $merchant = Db::name('merchant_account')
            ->where(['mch_id' => $orderInfo['mch_id'], 'channel' => $orderInfo['channel']])
            ->find();

        //查询三方渠道商户号账户表
        $channelMerchant = Db::name('channel_merchant_balance')
            ->where(['channel_merchant' => $orderInfo['channel_merchant_no'], 'channel' => $orderInfo['channel']])
            ->find();

        if (!empty($orderInfo['agents_id'])) {
            $agentsAccount = Db::name('agents_account')
                ->where(['agents_id' => $orderInfo['agents_id'], 'channel' => $orderInfo['channel']])
                ->find();
        }
        $agentsUpdate = [];
        try {
            Db::startTrans();
            $money = round(bcsub($orderInfo['total_fee'], $orderInfo['service_charge'], 3), 2);
            if ($orderInfo['jiesuan_status'] == 0) { // 0 未结算
                // 商户账户更新
                $merchantUpdate['sum_balance'] = ['exp', 'sum_balance - ' . $orderInfo['total_fee']]; //zong额
                $merchantUpdate['balance'] = ['exp', 'balance - ' . $money]; //余额
                $merchantUpdate['service_charge'] = ['exp', 'service_charge - ' . $orderInfo['service_charge']]; // 手续费
                $merchantUpdate['unliquidated_money'] = ['exp', 'unliquidated_money - ' . $money]; // 未结算金额

                // 三方商户号
                $channelMerchantUpdate['sum_balance'] = ['exp', 'sum_balance - ' . $orderInfo['net_profit']]; //zong
                $channelMerchantUpdate['balance'] = ['exp', 'balance - ' . $orderInfo['net_profit']]; //余额
                $channelMerchantUpdate['unliquidated_money'] = ['exp', 'unliquidated_money - ' . $orderInfo['net_profit']]; // 未结算金额

                // 是否为代理商 channelaccount
                if (!empty($orderInfo['agents_id'])) {
                    $agentsUpdate['sum_balance'] = ['exp', 'sum_balance - ' . $orderInfo['agents_income']]; //余额
                    $agentsUpdate['unliquidated_money'] = ['exp', 'unliquidated_money - ' . $orderInfo['agents_income']]; // 未结算金额
                }
            } elseif ($orderInfo['jiesuan_status'] == 1) { // 1已结算
                // 商户账户更新
                $merchantUpdate['sum_balance'] = ['exp', 'sum_balance - ' . $orderInfo['total_fee']]; //余额
                $merchantUpdate['balance'] = ['exp', 'balance - ' . $money]; //余额
                $merchantUpdate['service_charge'] = ['exp', 'service_charge - ' . $orderInfo['service_charge']]; // 手续费
                $merchantUpdate['withdraw_cash_balance'] = ['exp', 'withdraw_cash_balance - ' . $money]; // 未结算金额

                // 三方商户号
                $channelMerchantUpdate['sum_balance'] = ['exp', 'sum_balance - ' . $orderInfo['net_profit']]; //zong
                $channelMerchantUpdate['balance'] = ['exp', 'balance - ' . $orderInfo['net_profit']]; //余额
                $channelMerchantUpdate['withdraw_cash_balance'] = ['exp', 'withdraw_cash_balance - ' . $orderInfo['net_profit']]; // 未结算金额
                // 是否为代理商
                if (!empty($orderInfo['agents_id'])) {
                    $agentsUpdate['sum_balance'] = ['exp', 'sum_balance - ' . $orderInfo['agents_income']]; //余额
                    $agentsUpdate['withdraw_cash_balance'] = ['exp', 'withdraw_cash_balance - ' . $orderInfo['agents_income']]; // 未结算金额
                }
            }

            // 退款时间
            Db::name('order')
                ->where('id', '=', $id)
                ->update(['status' => 2, 'refund_time' => time()]);

            // 商户
            Db::name('merchant_account')
                ->where(['mch_id' => $orderInfo['mch_id'],
                    'channel' => $orderInfo['channel']
                ])
                ->update($merchantUpdate);
            // 减少商户账户余额明细
            Db::name('merchant_balance_record')->insert([
                'mch_id' => $orderInfo['mch_id'],
                'type' => 2,
                'channel' => $orderInfo['channel'],
                'money' => $money,
                'befor_money' => $merchant['balance'],
                'after_money' => $merchant['balance'] - $money,
                'remark' => '订单[' . $orderInfo['order_no'] . ']|退款' . $money . '元',
                'created_at' => time()
            ]);

            // 三方商户号
            Db::name('channel_merchant_balance')
                ->where('id', '=', $channelMerchant['id'])
                ->update($channelMerchantUpdate);
            // 减少三方商户号账户余额明细
            Db::name('channel_merchant_balance_record')->insert([
                'channel_merchant' => $orderInfo['channel_merchant_no'],
                'type' => 2,
                'channel' => $orderInfo['channel'],
                'money' => $orderInfo['net_profit'],
                'befor_money' => $channelMerchant['balance'],
                'after_money' => $channelMerchant['balance'] - $orderInfo['net_profit'],
                'remark' => '订单[' . $orderInfo['order_no'] . ']|退款' . $orderInfo['net_profit'] . '元',
                'created_at' => time()
            ]);

            // 代理商
            if (!empty($orderInfo['agents_id'])) {
                Db::name('agents_account')
                    ->where(['id' => $agentsAccount['id']])
                    ->update($agentsUpdate);

                // 减少代理总金额，只要结算的才会增加总利润中
                if ($orderInfo['jiesuan_status'] == 1) {
                    Db::name('agents')->where(['id' => $orderInfo['agents_id']])
                        ->setDec('total_amount', $orderInfo['agents_income']);
                }

                // 减少代理商户号账户余额明细
                Db::name('agents_balance_record')->insert([
                    'agents_id' => $agentsAccount['agents_id'],
                    'salesman_id' => $orderInfo['salesman_id'],
                    'record_type' => 2,
                    'type' => 2,
                    'channel' => $orderInfo['channel'],
                    'money' => $orderInfo['agents_income'],
                    'before_money' => $agentsAccount['sum_balance'],
                    'after_money' => $agentsAccount['sum_balance'] - $orderInfo['agents_income'],
                    'remark' => '订单[' . $orderInfo['order_no'] . '] 退款' . $orderInfo['agents_income'] . '元',
                    'created_at' => time(),
                ]);
            }
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            Log::error($orderInfo['order_no'] . ' [退款] 订单异常' . $e->getMessage() . '|' . $e->getTraceAsString());
            responseJson(false, -1, '操作失败');
        }
        responseJson(true, 0, '退款成功!');
    }
}