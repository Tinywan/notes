<?php

namespace app\admin\controller;

use app\common\controller\AdminController;

class PaymentInterfaceOrderController extends AdminController {

    use \app\common\traits\controller\Curd;

    public function model(){ return \app\common\model\InterfaceOrder::class; }

    public function init()
    {
        $this->route = 'admin/payment_interface_order';
        $this->label = '代付订单列表';
        $this->function['create'] = 0;
        $this->function['edit'] = 0;
        $this->function['delete'] = 0;
        $this->where['type'] = 1;

        $this->translations = [
            'id' => ['text' => '序号'],
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
            'payment_interface_id' => ['text' => '代付商户id'],
            'order_no' => ['text' => '系统订单号'],
            'mch_order_no' => ['text' => '商户订单号'],
            'channel_order_no' => ['text' => '代付商订单号'],
            'merchant_number' => ['text' => '代付商户号'],
            'goods' => ['text' => '商品名'],
            'price' => ['text' => '金额'],
            'total_fee' => ['text' => '总金额'],
            'type' => [
                'text' => '订单类型',
                'type' => 'radio',
                'list' => [
                    1 => ['label label-info', '代付订单'],
                    2 => ['label label-default', '备付金变动', true],
                ]

            ],
            'service_charge' => ['text' => '手续费'],
            'status' => [
                'text' => '状态',
                'type' => 'radio',
                'list' => [
                    -1 => ['label label-danger', '失败'],
                    0 => ['label label-default', '未支付', true],
                    1 => ['label label-warning', '处理中'],
                    2 => ['label label-success', '成功'],
                    3 => ['label label-default', '商户订单']
                ]

            ],
            'notify_status' => [
                'text' => '通知',
                'type' => 'radio',
                'list' => [
                    'no' => ['label label-default', '未通知'],
                    'yes' => ['label label-success', '成功'],
                    'fail' => ['label label-danger', '失败'],
                ]
            ],
            'notify_count' => ['text' => '通知次数'],
            'last_notify_time' => ['text' => '最后通知时间'],
            'create_time' => ['text' => '创建时间', 'type' => 'time'],
            'pay_time' => ['text' => '交易时间', 'type' => 'time'],
            'channel_return_data' => ['text' => '渠道请求结果数据(json_encode）'],
            'notify_url' => ['text' => '异步通知地址'],
            'customer_name' => ['text' => '收款客户姓名'],
            'account_number' => ['text' => '收款人银行卡'],
            'net_profit' => ['text' => '净利润'],
            'remark' => ['text' => '备注'],
        ];

        $this->listFields = ['mch_id', 'order_no', 'price', 'service_charge', 'total_fee', 'status', 'notify_status', 'remark', 'create_time', 'pay_time'];

        $this->updateFormFields = ['id', 'mch_id', 'payment_interface_id', 'order_no', 'mch_order_no', 'channel_order_no', 'merchant_number',
            'price', 'goods', 'type', 'service_charge', 'status', 'notify_status', 'notify_url', 'customer_name', 'account_number', 'net_profit', 'remark', 'create_time', 'pay_time'];

        $this->searchFields = ['mch_id', 'order_no', 'mch_order_no', 'channel_order_no', 'status', 'create_time'];

        $this->readFields = ['id', 'mch_id', 'payment_interface_id', 'order_no', 'mch_order_no', 'channel_order_no', 'merchant_number',
            'price', 'goods', 'type', 'service_charge', 'status', 'notify_status', 'notify_url', 'customer_name', 'account_number', 'net_profit', 'remark', 'create_time', 'pay_time'];
        $this->moreFunction = [
            [
                'btn' => 'warning',
                'icon' => 'fa fa-send',
                'text' => '通知',
                'route' => 'admin/payment_interface_order/sendNotify',
                'model_x' => '550px',
                'model_y' => '85%',
            ],
            [
                'btn' => 'info',
                'icon' => 'fa fa-search',
                'text' => '查询',
                'route' => 'admin/payment_interface_order/OrderQuery',
                'model_x' => '50%',
                'model_y' => '40%',
            ],
            [
                'btn' => 'default',
                'icon' => 'fa fa-search',
                'text' => 'V2查询',
                'route' => 'admin/payment_interface_order/orderQueryNew',
                'model_x' => '50%',
                'model_y' => '40%',
            ],
        ];
    }

    /**
     * 重发通知
     */
    public function sendNotify(PaymentInterfaceRepository $paymentInterfaceRepository)
    {
        $id = request()->get('ids');
        $orderInfo = InterfaceOrder::get($id);
        if (empty($orderInfo)) {
            $this->error('订单不存在', '', '', -1);
        }
//        if($orderInfo['status'] != 2 || $orderInfo['status'] != -1){
//            $this->error('订单未代付成功', '', '', -1);
//        }
        $result = $paymentInterfaceRepository->sendMerchantNotify($orderInfo['order_no'], 2);
        Log::debug('[代付] 手动发送通知结果 ' . json_encode($result));
        if (!$result) {
            $error = $paymentInterfaceRepository->getError();
            $html = '<html>
                <head>
                <meta charset="UTF-8">
                <title>返回结果</title>
                </head>
                <body>
                <h3 style="text-align: center"> 通知失败，返回以下内容 </h3><hr/>
                ' . htmlspecialchars($error['message']) . '
                </body>
            </html>';
            echo $html;
        } else {
            $this->success('发送通知成功！', '', '', -1);
        }
    }

    /**
     * 订单主动查询
     * @param PaymentInterfaceRepository $paymentInterfaceRepository
     * @return $this
     * @throws \think\exception\DbException
     */
    public function orderQuery(PaymentInterfaceRepository $paymentInterfaceRepository)
    {
        $id = request()->get('ids');
        $orderInfo = InterfaceOrder::get($id);
        if (empty($orderInfo)) {
            $this->error('订单不存在', '', '', -1);
        }
        $option['mch_id'] = $orderInfo['mch_id'];
        $option['mch_order_no'] = $orderInfo['mch_order_no'];
        $option['merchant_number'] = $orderInfo['merchant_number'];
        $res = $paymentInterfaceRepository->orderQuery($option);
        return view()->assign([
            'list' => $res
        ]);
    }

    /**
     * [V2] 订单主动查询
     * @param PaymentInterfaceRepository $paymentInterfaceRepository
     * @return $this
     * @throws \think\exception\DbException
     */
    public function orderQueryNew()
    {
        $id = request()->get('ids');
        $orderInfo = InterfaceOrder::get($id);
        if (empty($orderInfo)) {
            $this->error('订单不存在', '', '', -1);
        }
        $channel = $orderInfo['channel'];
        if ($channel == 'gomepay') {
            // 美付宝查询
            $option['mch_id'] = $orderInfo['mch_id'];
            $option['mch_order_no'] = $orderInfo['mch_order_no'];
            $option['merchant_number'] = $orderInfo['merchant_number'];
        } elseif ($channel == 'respay') {
            $option['mch_id'] = $orderInfo['mch_id'];
            $option['mch_order_no'] = $orderInfo['mch_order_no'];
            $option['merchant_number'] = $orderInfo['merchant_number'];
        }

        $class = config("agent_class");
        $channel = strtolower($channel);
        $repository = new AgentServiceRepository(App::invokeClass($class[$channel]));
        $res = $repository->orderQuery($option);
        Log::debug('[V2]通道订单查询返回数据 ' . json_encode($res));
        //$this->fetch();
        return view('orderquery')->assign([
            'list' => $res['data']
        ]);
    }
}