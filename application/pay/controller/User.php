<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/7/19 22:59
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: Service本身译为服务
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\pay\controller;

use app\common\controller\PayController;
use app\pay\service\UserService;
use think\App;
use think\facade\Request;

class User extends PayController
{
    public $request;

    /**
     * 服务层实例
     * @var UserService
     */
    protected $userService;

    /**
     * User constructor.
     * @param App|null $app
     * @param Request $request
     * @param UserService $userService
     */
    public function __construct(App $app = null, Request $request, UserService $userService)
    {
        $this->request = $request;
        $this->userService = $userService;
    }

    /**
     * 用户注册
     * @return bool
     */
    public function register()
    {
        return $this->userService->register($this->request->param());
    }

    public function getUserInfo()
    {
        //... validation
        return $this->userService->getUserInfo($this->request->all());
    }
}