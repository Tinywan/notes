<?php

namespace app\admin\controller;

use app\common\controller\AdminController as BaseController;
use app\common\model\Admin as AdminModel;
use app\common\model\AuthAdminGroupAccess;
use app\common\traits\controller\Curd;
use think\facade\Validate;

class AdminController extends BaseController
{
    use Curd;

    /**
     * 模型
     * @return mixed
     */
    function model()
    {
        return AdminModel::class;
    }

    /**
     * 初始化
     * @return mixed
     */
    function init()
    {
        $this->label = '管理员';
        $this->route = 'admin/admin';

        $this->translations = [
            'id' => ['text' => '#'],
            'username' => ['text' => '管理员账号'],
            'group_id' => [
                'text' => '用户组',
                'type' => 'join',
                'data' => [
                    'table' => 'auth_admin_group',
                    'alias' => 'b',
                    'show_field' => 'title',
                    'value_field' => 'id',
                ]],
            'title' => ['text' => '用户组'],
            'password' => ['text' => '管理员密码', 'type' => 'password'],
            'login_time' => ['text' => '最后登录时间', 'type' => 'time'],
            'login_ip' => ['text' => '最后登录ip', 'type' => 'ip'],
            'created_at' => ['text' => '添加时间', 'type' => 'time'],
            'updated_at' => ['text' => '更新时间', 'type' => 'time'],
            'status' => [
                'text' => '状态',
                'type' => 'radio',
                'list' => [
                    -1 => ['label label-default', '禁用'],
                    1 => ['label label-success', '正常', true]
                ]
            ],
        ];

        $this->listFields = ['id', 'username', 'title', 'login_time', 'login_ip', 'status', 'created_at', 'updated_at'];
        $this->addFormFields = ['username', 'group_id', 'password', 'status'];
        $this->updateFormFields = ['username', 'group_id', 'password', 'status'];

    }

    public function save()
    {
        $data = request()->post();

        $validate = Validate::make($this->getValidateRule(), [], $this->getValidateFieldName());
        $result = $validate->check($data);

        if (!$result) {
            if (request()->isAjax()) {
                return responseJson(false, -1, $validate->getError());
            } else {
                $this->error($validate->getError());
            }
        }
        $data['created_at'] = time();
        $data['updated_at'] = time();
        $data = $this->disposeData($data);
        $res = $this->model->allowField(true)->save($data);
        if ($res) {
            // 添加权限组关联
            AuthAdminGroupAccess::create([
                'uid' => $this->model->id,
                'group_id' => $data['group_id']
            ]);

            add_operateLogs('添加[' . $this->label . ']记录');
            return responseJson(true, 0, '添加成功！');
        } else {
            return responseJson(false, -1, '添加失败！');
        }
    }

    /**
     * 删除
     * @return \think\Response|\think\response\Json|\think\response\Jsonp|\think\response\Redirect|\think\response\View|\think\response\Xml
     */
    public function delete()
    {
        $id = request()->param('id');
        if (empty($id)) {
            return responseJson(false, -1, '请选择要删除的记录!');
        }
        $id = trim($id, ',');
        $id_array = explode(',', $id);

        if (in_array(1, $id_array)) {
            return responseJson(false, -1, '预留超级管理员禁止删除!');
        }

        $res = AdminModel::destroy($id_array);
        if ($res) {
            // 删除权限组关联
            AuthAdminGroupAccess::destroy($id_array);
            add_operateLogs('删除[' . $this->label . ']记录');
            return responseJson(true, 0, '删除成功!');
        } else {
            return responseJson(false, -1, '删除失败!');
        }
    }

    protected function getEditValidateRule($id)
    {
        return [
            'username' => 'require|unique:admin,username,' . $id,
            'group_id' => 'require',
            'status' => 'require'
        ];
    }

    protected function getValidateRule()
    {
        return [
            'username' => 'require|unique:admin',
            'group_id' => 'require',
            'status' => 'require'
        ];
    }
}
