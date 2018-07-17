<?php

namespace app\admin\controller;

use app\common\controller\AdminController;

class OrderController extends AdminController {

    use \app\common\traits\controller\Curd;

    public function model(){ return \app\common\model\Order::class; }

    public function init()
    {
        $this->route = 'admin/order';
        $this->label = '订单列表';
        $this->function['create'] = 0;
        $this->where['type'] = 1;

        $this->translations = [
          'id' => ['text' => 'ID'],
          'mch_id' => [
            'text' => '商户',
            'type' => 'join',
            'data' => [
              'table' => 'merchant',
              'alias' => 'b',
              'show_field' => 'merchant_name',
              'value_field' => 'id',
            ]
          ],
          'order_no' => ['text' => '订单号'],
          'mch_order_no' => ['text' => '商户订单号'],
          'channel_order_no' => ['text' => '渠道订单号'],
          'goods' => ['text' => '商品名'],
          'total_amount' => ['text' => '金额'],
          'channel' => [
            'text' => '渠道',
          ],
          'channel_merchant_no' => ['text' => '三方商户号'],
          'payment' => [
            'text' => '支付方式',
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
              2 => ['label label-default', '已退款'],
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

        $this->listFields = ['mch_id', 'order_no', 'mch_order_no', 'channel_order_no', 'total_amount', 'channel', 'cost_rate', 'rate', 'service_charge', 'status', 'notify_status', 'create_time', 'pay_time'];

        $this->addFormFields = $this->updateFormFields = ['total_amount', 'rate', 'service_charge', 'status', 'pay_time'];

        $this->searchFields = ['mch_id', 'order_no', 'mch_order_no', 'channel_order_no', 'channel', 'channel_merchant_no', 'payment', 'status', 'create_time'];

        $this->excelFields = ['mch_id', 'order_no', 'mch_order_no', 'channel_order_no', 'total_fee', 'channel', 'channel_merchant_no', 'payment', 'cost_rate', 'rate', 'service_charge', 'status', 'notify_status', 'create_time', 'pay_time'];
    }
}