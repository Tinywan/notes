<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/8/29 23:05
 * |  Mail: 756684177@qq.com
 * |  Desc: 描述信息
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\common\model;


use think\facade\Cache;
use think\facade\Log;
use think\Model;

class BaseModel extends Model
{
    /**
     * [初始化处理]
     */
    protected static function init()
    {
        /**
         * 更新之后操作，$model 接受到的是修改的数据库
         * $model =
         */
        self::beforeUpdate(function ($model) {
            Log::error('更新之前操作：' . json_encode($model));
        });

        self::afterUpdate(function ($model) {
//            Log::error('模型名称：'.$model->name); // "User"
//            Log::error('数据表名称：'.$model->table); // "pay_open_user"
//            Log::error('数据表PK ：'.$model->pk); // "id"
//            Log::error('更新数据：'.json_encode($model)); // {"id":11,"open_id":1535555636,"realname":"Tinywan1022"}
            $cacheName = $model->table .PATH_SEPARATOR. $model[$model->pk];
            Cache::rm($cacheName);
        });
    }
}