<?php

namespace app\backend\controller;

use app\common\controller\BaseBackendController;
use app\common\model\AdminSidebar;
use app\common\model\AuthAdminGroup;
use app\common\model\AuthRule;
use app\common\traits\controller\Curd;

class AuthAdminGroupController extends BaseBackendController
{
    use Curd;

    public function model(){
        return AuthAdminGroup::class;
    }

    public function init(){
        $this->route = 'backend/auth_admin_group';
        $this->label = '用户组';
        $this->translations = [
            'id'  => ['text' => '#'],
            'title'  => ['text' => '组名称'],
            'status'  => [
                'text' => '状态',
                'type' => 'radio',
                'list'=> [
                    0 => ['label label-default', '禁用'],
                    1 => ['label label-info', '开启', true]
                ]
            ],
            'rules'  => ['text' => '组权限', 'type' => 'checkbox'],
            'created_at'  => ['text' => '添加时间', 'type' => 'time'],
            'updated_at'  => ['text' => '更新时间', 'type' => 'time'],
        ];

        $this->listFields = ['id', 'title', 'status', 'created_at', 'updated_at'];
        $this->addFormFields = ['title', 'status', 'rules'];
        $this->updateFormFields = ['title', 'status', 'rules'];
    }

    /**
     * 重写添加
     */
    public function create()
    {
        $rule = $this->getAuthRule();
        return view()->assign('rule', $rule);
    }

    /**
     * 重写编辑
     */
    public function edit($id)
    {
        $rule = $this->getAuthRule();
        $info = $this->model->get($id);
        if (empty($info)){
            $this->error('记录不存在！');
        }
        $info = $info->toArray();
        $rule_array = explode(',', trim($info['rules'], ','));

        foreach ($rule as $k => $v) {
            $_on = 'no';
            if (!empty($v['child'])){
                foreach ($v['child'] as $_k => $_v){
                    $_on = 'no';
                    if (!empty($_v['role_list'])){
                        foreach ($_v['role_list'] as $__k => $__v) {
                            if (in_array($__v['id'], $rule_array)){
                                $_on = 'yes';
                                $rule[$k]['child'][$_k]['role_list'][$__k]['active'] = 'yes';
                            }else{
                                $rule[$k]['child'][$_k]['role_list'][$__k]['active'] = 'no';
                            }
                        }
                    }
                    $rule[$k]['child'][$_k]['active'] = $_on;
                }
                $rule[$k]['active'] = $_on;
            }
        }

        return view()->assign([
            'rule' => $rule,
            'info' => $info,
            'id'   => $id
        ]);
    }

    /**
     * 获取权限规则
     */
    protected function getAuthRule(){
        $rule_list = AuthRule::all(['status' => 1]);
        $one_menu_list = AdminSidebar::where(['status' => 1, 'tid' => 0])->order('sort')->select();
        $two_menu_list = AdminSidebar::where(['status' => 1, 'tid' => ['neq', 0]])->order('sort')->select();

        $two_list = [];

        foreach ($two_menu_list as $k => $v) {
            $item = $v->toArray();
            foreach ($rule_list as $_k => $_v) {
                if ($_v['tid'] == $v['id']){
                    $item['role_list'][] = $_v->toArray();
                    unset($rule_list[$_k]);
                }
            }
            $two_list[] = $item;
        }

        $result = [];

        foreach ($one_menu_list as $k => $v) {
            $item = $v->toArray();
            foreach ($two_list as $_k => $_v) {
                if ($v['id'] == $_v['tid']){
                    $item['child'][] = $_v;
                    unset($two_list[$_k]);
                }
            }
            $result[] = $item;
        }

        return $result;
    }
}
