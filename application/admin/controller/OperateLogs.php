<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/7/6 20:46
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 描述信息
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\admin\controller;


use app\common\model\AdminOperateLogs;
use app\common\traits\controller\Curd;
use app\common\controller\AdminController;


class OperateLogs extends AdminController
{
    use Curd;

    public function model()
    {
        return AdminOperateLogs::class;
    }

    /**
     * 初始化
     * @return mixed
     */
    function init()
    {
        $this->where['from'] = 'admin';
        $this->label = '系统日志';
        $this->route = 'admin/operatelogs';
        $this->function['create'] = 0;
        $this->function['edit'] = 0;
        $this->function['delete'] = 0;

        $this->translations = [
          'id' => ['text' => '#'],
          'uid' => [
            'text' => '管理员',
            'type' => 'join',
            'data' => [
              'table' => 'admin',
              'alias' => 'b',
              'show_field' => 'username',
              'value_field' => 'id',
            ]
          ],
          'username' => ['text' => '管理员'],
          'remark' => ['text' => '操作备注'],
          'ip' => ['text' => 'ip地址', 'type' => 'ip'],
          'type' => [
            'text' => '类型',
            'type' => 'radio',
            'list' => [
              1 => ['label label-success', '操作日志', true],
              2 => ['label label-info', '登录日志']
            ]
          ],
          'created_at' => ['text' => '添加时间', 'type' => 'time']
        ];

        $this->listFields = ['id', 'username', 'remark', 'ip', 'type', 'created_at'];

        $this->searchFields = ['username', 'remark', 'ip', 'type'];
    }
}