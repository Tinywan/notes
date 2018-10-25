<?php

namespace app\common\model;

use think\Model;

class AgentsCard extends Model
{
    protected $autoWriteTimestamp = true;

    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    protected $insert = [
        'created_at'
    ];

    protected $update = [
        'updated_at'
    ];
}
