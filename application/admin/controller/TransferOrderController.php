<?php

namespace app\admin\controller;

use app\common\controller\AdminController;
use think\Db;
use think\Exception;
use think\facade\Log;

class TransferOrderController extends AdminController {

    use \app\common\traits\controller\Curd;

    public function model(){ return \app\common\model\Order::class; }

    public function init(){
        $this->route = 'admin/transfer_order';
        $this->label = '转账订单';
        $this->function['create'] = 0;

        $this->where['type'] = 2;

        $this->translations = [
            'id'  => ['text' => 'ID'],
            'mch_id'  => [
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
            'order_no'  => ['text' => '订单号'],
            'mch_order_no'  => ['text' => '商户订单号'],
            'channel_order_no'  => ['text' => '渠道订单号'],
            'goods'  => ['text' => '商品名'],
            'price'  => ['text' => '订单金额'],
            'total_fee'  => ['text' => '实付金额'],
            'cost_rate'  => ['text' => '成本费率'],
            'rate'  => ['text' => '费率'],
            'service_charge'  => ['text' => '手续费'],
            'agents_rate' => ['text' => '代理费率'],
            'agents_income' => ['text' => '代理手续费'],
            'status'  => [
                'text' => '支付状态',
                'type' => 'radio',
                'list'=> [
                    -1 => ['label label-danger', '支付失败'],
                    0 => ['label label-default', '未支付', true],
                    1 => ['label label-info', '已支付']
                ]

            ],
            'pay_type' => [
                'text' => '支付方式',
                'type' => 'radio',
                'list'=> [
                    1 => ['label label-success', '支付宝'],
                    2 => ['label label-info', '微信']
                ]
            ],
            'mch_user_id' => ['text' => '用户id'],
            'account' => ['text' => '收款账号'],
            'qrcode' => ['text' => '支付二维码'],
            'notify_status'  => [
                'text' => '通知状态',
                'type' => 'radio',
                'list'=> [
                    'no' => ['label label-default', '未通知'],
                    'yes' => ['label label-info', '成功'],
                    'fail' => ['label label-danger', '失败'],
                ]
            ],
            'create_time'  => ['text' => '创建时间', 'type' => 'time'],
            'pay_time'  => ['text' => '支付时间', 'type' => 'time'],
            'refund_time'  => ['text' => '退款时间', 'type' => 'time'],
            'channel_return_data'  => ['text' => '渠道返回数据'],
        ];

        $this->listFields = ['mch_id', 'agents_id', 'order_no', 'mch_order_no', 'pay_type', 'price', 'total_fee', 'rate', 'service_charge','agents_rate', 'agents_income', 'status', 'notify_status', 'create_time', 'pay_time'];
        $this->addFormFields = $this->updateFormFields = ['total_fee', 'rate', 'service_charge', 'status', 'pay_time'];
        $this->searchFields = ['mch_id', 'agents_id', 'order_no', 'mch_order_no', 'channel_order_no', 'status', 'notify_status', 'create_time'];
        $this->readFields = ['mch_id', 'agents_id', 'order_no', 'mch_order_no', 'account', 'pay_type', 'price', 'total_fee', 'rate', 'service_charge','agents_rate', 'agents_income', 'status', 'notify_status', 'create_time', 'pay_time'];
    }
}