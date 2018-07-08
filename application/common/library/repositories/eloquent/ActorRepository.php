<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/7/8 10:56
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 描述信息
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\common\library\repositories\eloquent;


use app\common\library\repositories\eloquent\Repository;
use app\common\model\Admin;

class ActorRepository extends PayAbstractRepository
{
    public function model()
    {
        return Admin::class;
    }
}