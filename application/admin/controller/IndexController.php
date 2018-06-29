<?php

namespace app\admin\controller;

use app\common\controller\BaseBackendController;
use app\common\model\AdminSidebar;
use app\common\model\Admin;
use app\common\model\BackendSidebar;
use think\facade\Session;

class IndexController extends BaseBackendController
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        $sidebar = $this->getSidebar();
        $this->assign('sidebar', $sidebar);
        return $this->fetch();
    }

    public function session()
    {
        $res = Admin::get(1);
        $sidebar = Session::set('admin_info',$res);
        halt($sidebar);
        $this->assign('sidebar', $sidebar);
        return $this->fetch();
    }

    public function welcome()
    {
        return view();
    }

    public function live()
    {
        return view();
    }

    /**
     * 获取侧边栏
     * @return array
     */
    private function getSidebar()
    {
        $side = AdminSidebar::where(['tid' => 0, 'status' => 1])
          ->order('sort')
          ->select();
        $side_child = AdminSidebar::where('tid','<>',0)->where('status','=', 1)
          ->order('sort')
          ->select();
        $list = [];
        foreach ($side as $key => $value) {
            $list[$key] = $value->toArray();
            $child = [];
            foreach ($side_child as $_key => $_value) {
                $_value = $_value->toArray();
                if ($_value['tid'] == $value['id']) {
                    if ($this->admin_info['id'] == 1) {
                        $child[] = $_value;
                    } else {
                        if ($this->auth->check($_value['url'], $this->admin_info['id'])) {
                            $child[] = $_value;
                        }
                    }
                    unset($side_child[$_key]);
                }
            }
            if (!empty($child)) {
                $list[$key]['child'] = $child;
            } else {
                unset($list[$key]);
            }
        }
        return $list;
    }
}
