<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/6/13 21:23
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 描述信息
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\admin\controller;


use app\common\model\SystemConfig;
use app\common\traits\controller\Curd;
use app\common\controller\AdminController;

class SystemConfigController extends AdminController
{
    use Curd;

    public function model()
    {
        return SystemConfig::class;
    }

    public function init(){
        $this->route = 'admin/system_config';
        $this->label = '系统配置';
        $this->order = ['id' => 'asc'];

        $this->translations = [
          'id'  => ['text' => '#'],
          'config_remark'  => ['text' => '配置名称'],
          'config_name'  => ['text' => '配置变量'],
          'config_value'  => ['text' => '配置值', 'info' => '非必填', 'validate_rule' => false],
          'group_id'  => [
            'text' => '分组',
            'type' => 'radio',
            'list'=> config('config_group')
          ],
          'field_type'  => [
            'text' => '字段类型',
            'type' => 'radio',
            'list'=> [
              'input' => ['', 'input', true],
              'textarea' => ['', 'textarea'],
              'umeditor' => ['', 'umeditor'],
              'radio' => ['', 'radio'],
              'time' => ['', 'time'],
            ]
          ],
          'is_show' => [
            'text' => '是否显示',
            'type' => 'radio',
            'list' => [
              1 => ['label label-success', '显示', true],
              0 => ['label label-info', '隐藏']
            ]
          ],
          'filed_comment' => ['text' => '字段解释', 'info' => '非必填，配置说明', 'validate_rule' => false],
          'from_data' => ['text' => '数据源', 'type' => 'textarea', 'info' => '非必填', 'validate_rule' => false],
          'created_at'  => ['text' => '添加时间', 'type' => 'time'],
          'updated_at'  => ['text' => '更新时间', 'type' => 'time'],
        ];

        $this->listFields = ['id', 'config_remark', 'config_name', 'group_id', 'created_at', 'updated_at'];
        $this->addFormFields = $this->updateFormFields = ['config_remark', 'config_name', 'group_id', 'field_type', 'is_show', 'filed_comment', 'from_data'];

        $this->searchFields = ['config_remark', 'config_name', 'group_id'];
    }
}