<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/3/27 22:34
 * |  Mail: Overcome.wan@Gmail.com
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\common\model;


use think\Model;

class WxOrder extends Model
{
    protected $table = 'resty_wx_order';

    protected $hidden = [
        'delete_time',
        'create_time',
        'update_time'
    ];

    protected $resultSetType = 'collection';

    protected $autoWriteTimestamp = true;

    protected $insert = [
        "create_time"
    ];

    //更新自动完成
    protected $update = [
        "update_time"
    ];

    public static function getSummaryByUser($uid, $page = 1, $size = 15)
    {
        $res = self::where('user_id','=',$uid)
            ->order('create_time desc')
            ->paginate($size,true,['page'=>$page]);
        return $res;
    }

    /**
     * 读取器
     */
    public function getSnapItemsAttr($value){
        if(empty($value)) return null;
        return json_decode($value);
    }

    public function getSnapAddressAttr($value){
        if(empty($value)) return null;
        return json_decode($value);
    }
}