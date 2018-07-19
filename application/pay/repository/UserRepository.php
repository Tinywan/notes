<?php

/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/7/19 23:08
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 描述信息
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\pay\repository;

use app\common\model\User;

class UserRepository
{
    /**
     * 获取用户基本信息
     * @param $data
     * @return array|null|\PDOStatement|string|\think\Model
     */
    public function getUserInfo($data)
    {
        $userId = $data['user_id'];
        $result = User::where('id', '=', $userId)->find();
        return $result;
    }
}