<?php

namespace app\api\validate;

use think\Validate;

class Gateway extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'	=>	['规则1','规则2'...]
     * @var array
     */
    protected $rule = [
        'mch_id' => 'require',
        'method' => 'require',
        'version' => 'require',
        'timestamp' => 'require',
        'content' => 'require',
        'sign' => 'require',
    ];

    /**
     * 格式：'字段名.规则名'	=>	'错误信息'
     * @var array
     */
    protected $message = [
        'mch_id.require'  => '商户号不能为空',
        'method.require'  => '接口方法不能为空',
        'version.require'  => '版本号不能为空',
        'timestamp.require'  => '请求时间戳不能为空',
        'content.require'  => '请求主体内容不能为空',
        'sign.require'  => '签名不能为空',
    ];

    /**
     * 验证场景
     * @var array
     */
    protected $scene = [
        'payDo'    => ['mch_id', 'method', 'version', 'timestamp', 'content','sign']
    ];
}
