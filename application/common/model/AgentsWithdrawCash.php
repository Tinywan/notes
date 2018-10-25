<?php

namespace app\common\model;

use think\Model;

class AgentsWithdrawCash extends Model
{
    protected $autoWriteTimestamp = true;

    protected $createTime = 'created_at';

    // 关闭自动写入update_time字段
    protected $updateTime = false;

    protected $insert = [
        'created_at'
    ];

    protected $update = [
        'updated_at'
    ];
}
