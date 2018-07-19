<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/7/19 23:02
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: Service本身译为服务
 * |  1、将外部方法，公共方法注入到Service
 * |  2、将Service注入到控制器
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\pay\service;


use app\common\model\User;
use app\pay\repository\UserRepository;

class UserService
{
    public $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function getUserInfo($data)
    {
        return $this->userRepository->getUserInfo($data);
    }

    public function register($data)
    {
        $password = md5($data['password']);
        $user = new User();
        $user->username = $data['username'];
        $user->password = $password;
        $result = $user->save();
        return $result;
    }
}