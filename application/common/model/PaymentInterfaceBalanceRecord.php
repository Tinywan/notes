<?php

namespace app\common\model;

use think\Model;

class PaymentInterfaceBalanceRecord extends Model
{
    protected $auto = [
        'admin_id',
        'last_login_ip'
    ];

    protected function setLastLoginIpAttr()
    {
        return request()->ip();
    }

    protected function setAdminIdAttr()
    {
        return session('admin_info.id');
    }
}
