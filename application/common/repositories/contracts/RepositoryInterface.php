<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/8/21 15:51
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 支付渠道接口
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\common\repositories\contracts;


interface RepositoryInterface
{
    public function setError($success, $message);

    public function getError();
}