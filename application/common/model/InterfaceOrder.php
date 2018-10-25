<?php

namespace app\common\model;

use think\Model;

class InterfaceOrder extends Model
{
    protected $auto = ['create_time'];

    public function getCreateTimeAttr($time)
    {
        return $time;
    }
}
