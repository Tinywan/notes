<?php

namespace app\common\controller;

use app\common\library\Auth;
use think\facade\Cache;
use think\facade\Session;

class BaseBackendController extends BaseController
{
    protected $auth;
    protected $admin_info;
    protected $sidebar;

    public function __construct()
    {
        parent::__construct();
        //验证登录状态
        if (!$this->checkLogin()){
            $this->redirect('/backend/login');
        }

        $this->auth = new Auth();
        $this->admin_info = get_admin_info();

        // 验证权限(排除id为1的系统管理员)
        if ($this->admin_info['id'] != 1 && !$this->checkRole()){
            if ($this->request->isAjax()){
                return $this->error(false, -1, '权限不足！');
            }else{
                return $this->error('权限不足！');
            }
        }
    }

    /**
     * 验证登录
     * @return bool
     */
    private function checkLogin(){
        if (Session::has('admin_info')){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 验证权限
     * @param string $role
     * @return bool
     */
    protected function checkRole($role = ''){

        $uid = $this->admin_info['id'];
        $module = tf_to_xhx(request()->module());
        $controller = tf_to_xhx(request()->controller());
        $action = tf_to_xhx(request()->action());

        $routeName = $module.'/'.$controller.'/'.$action;
        //排除权限
        if (in_array($routeName, config('public'))){
            return true;
        }

        if ($role == ''){
            $role = $controller;
        }

        switch ($routeName) {
            case 'backend/'.$role.'/index':
                $permission = 'admin/'.$role.'/index'; break;
            case 'backend/'.$role.'/create':
                $permission = 'admin/'.$role.'/create'; break;
            case 'backend/'.$role.'/save':
                $permission = 'admin/'.$role.'/create'; break;
            case 'backend/'.$role.'/edit':
                $permission = 'admin/'.$role.'/edit'; break;
            case 'backend/'.$role.'/update':
                $permission = 'admin/'.$role.'/edit'; break;
                break;
            case 'backend/'.$role.'/delete':
                $permission = 'admin/'.$role.'/delete'; break;
                break;
            default:
                $permission = $routeName;
                break;
        }
        if (!$this->auth->check($permission, $uid)){
            return false;
        }else{
            return true;
        }
    }

    /**
     * 清除缓存
     * @return bool
     */
    protected function rmCache(){
        if (Cache::clear()){
            return true;
        }else{
            return false;
        }
    }
}
