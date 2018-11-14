<?php

namespace app\index\validate;

use think\Validate;

class User extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'	=>	['规则1','规则2'...]
     * @var array
     */
    protected $rule = [
        'user_name' => 'require|alphaNum',
        'password'  => 'require',
    ];

    /**
     * 格式：'字段名.规则名'	=>	'错误信息'
     * @var array
     */
    protected $message = [
        'user_name.require'  => '请输入账号',
        'user_name.alphaNum' => '账号只能是数字和字母',
        'password.require'   => '请输入密码',
    ];

    protected $scene = [
        'login'    => ['user_name', 'password'],
        'register' => ['user_name', 'password'],
    ];
}
