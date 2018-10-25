<?php

namespace app\common\model;

use think\Model;
use traits\model\SoftDelete;

class MerchantPaymentInterface extends Model
{
    use SoftDelete;

    protected $autoWriteTimestamp = true;

    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
    protected $deleteTime = "delete_time";

    protected $insert = [
        'created_at'
    ];

    protected $update = [
        'updated_at'
    ];
}
