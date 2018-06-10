<?php

namespace app\common\model;

use think\Model;

class AdminSidebar extends Model
{
    public static function getList($where = []){
        return self::where($where)->select();
    }
}
