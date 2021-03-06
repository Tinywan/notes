<?php

namespace app\common\model;

use think\Model;
use traits\model\SoftDelete;

class Agents extends Model
{
    use SoftDelete;

    protected $autoWriteTimestamp = true;

    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
    protected $deleteTime = "delete_time";

    protected $auto = [
        'admin_id',
        'last_login_ip'
    ];

    protected $insert = [
        'created_at'
    ];

    protected $update = [
        'updated_at'
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
