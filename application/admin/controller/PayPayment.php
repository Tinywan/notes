<?php

namespace app\admin\controller;

use app\common\controller\AdminController;

class PayPayment extends AdminController {

    use \app\common\traits\controller\Curd;

    public function model(){ return \app\common\model\PayPayment::class; }

    public function init(){
        $this->route = 'admin/pay_payment';
        $this->label = '支付方式';
        $this->translations = [
            'id'  => ['text' => ''],
            'icon'  => ['text' => '图标'],
            'payment_name'  => ['text' => '支付方式名称'],
            'key'  => ['text' => '支付方式标识'],
            'status'  => ['text' => '状态  0，1'],
            'created_at'  => ['text' => '添加时间'],
            'updated_at'  => ['text' => '更新时间'],
            ];
    }
}