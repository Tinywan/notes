<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/6/14 13:37
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 描述信息
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\index\controller;


use app\common\model\Admin as AdminModel;
use app\common\model\Admin;
use app\common\validate\Admin as AdminValidate;
use think\Controller;

class AdminController extends Controller
{
    // 模型添加数据验证
    public function saveAdmin()
    {
        $data = [
            'name' => 'thinkphp',
            'email' => 'thinkphp@qq.com',
        ];
        $validate = new AdminValidate;
        if (!$validate->check($data)) {
            dump($validate->getError());
        }
    }

    // 控制器验证
    public function saveAdmin02()
    {
        $data = [
            'name' => 'thinkphp',
            'email' => 'thinkphp@qq.com',
        ];
        $result = $this->validate($data,[
            'agents_name' => 'require|unique:agents',
            'phone' => 'require',
            'email' => 'require|email',
            'status' => 'require',
        ],[
            'agents_name.require'=>'用户名不能为空',
            'agents_name.unique'=>'用户名已经存在',
            'phone.require'=>'电话不能为空',
            'email.require'=>'邮箱不能为空',
            'email.email'=>'邮箱格式错误',
            'status.require'=>'状态是必须的',
        ]);
        $result = $this->validate($data,'BaseAdminController');
        if(true !== $result){
            // 验证失败 输出错误信息
            dump($result);
        }
    }
}