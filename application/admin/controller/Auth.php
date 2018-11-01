<?php

namespace app\admin\controller;

use app\common\controller\AdminController;
use app\common\model\AdminSidebar;
use app\common\model\AuthRule;
use think\Request;

class Auth extends AdminController
{
    /**
     * 权限节点
     * @param $id
     * @return \think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function rule($id){

        $sidebar = AdminSidebar::get($id);
        $rule = AuthRule::where(array('tid'=>$id))->select();

        $this->assign('rule', $rule);
        $this->assign('id', $id);
        $this->assign('title', $sidebar->name);

        $this->assign('function_rule', [
            'create' => check_role('admin/auth/addrule'),
            'edit' => check_role('admin/auth/editrule'),
            'delete' => check_role('admin/auth/delrule')
        ]);
        return view();
    }

    /**
     * 添加权限节点
     * @param $id
     * @return \think\Response|\think\response\Json|\think\response\Jsonp|\think\response\Redirect|\think\response\View|\think\response\Xml
     */
    public function addRule($id){
        if($this->request->isPost()){
            $data = $this->request->post();
            $rows = AuthRule::where(array('name' => $data['name']))->count();
            if($rows > 0){
                return responseJson(false, -1, '已存在该权限!');
            }
            $rule = AuthRule::create($data);
            if(!empty($rule)){
                add_operateLogs('添加权限节点['.$data['name'].']');

                return responseJson(true, 0, '添加成功!');
            }else{
                return responseJson(false, -1, '添加失败!');
            }
        }

        $this->assign('id', $id);
        return view();
    }

    /**
     * 编辑权限节点
     * @param $id
     * @return \think\Response|\think\response\Json|\think\response\Jsonp|\think\response\Redirect|\think\response\View|\think\response\Xml
     * @throws \think\exception\DbException
     */
    public function editRule($id){

        $rule = AuthRule::get($id);
        $name = $rule->name;
        if (empty($rule)){
            $this->error('权限节点不存在');
        }

        if($this->request->isPost()){
            $data = $this->request->post();
            $count = AuthRule::where(array(
                'name' => $data['name'],
                'id'   => array('neq', $id)
            ))->count();
            if($count > 0){
                return responseJson(false, -1, '权限节点已存在');
            }
            $rows = $rule->save($data);
            if($rows >= 0){
                add_operateLogs('编辑权限节点['.$name.']为['.$data['name'].']');
                return responseJson(true, 0, '编辑成功');
            }else{
                return responseJson(false, -1, '编辑失败');
            }
        }

        $this->assign('rule', $rule);
        $this->assign('id', $id);

        return view();
    }

    /**
     * 删除权限节点
     * @param $id
     * @return \think\Response|\think\response\Json|\think\response\Jsonp|\think\response\Redirect|\think\response\View|\think\response\Xml
     * @throws \think\exception\DbException
     */
    public function delRule(){
        $id = $this->request->post('id');
        if(empty($id)) return responseJson(false, -1, '参数错误');
        $rule = AuthRule::get($id);
        if(empty($rule)) return responseJson(false, -1, '权限节点不存在');

        $result = $rule->delete();
        if ($result){
            return responseJson(true, 0, '删除成功');
        }else{
            return responseJson(false, -1, '删除失败');
        }
    }
}
