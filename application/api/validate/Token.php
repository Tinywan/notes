<?php

namespace app\api\validate;

class Token extends BaseValidate
{
    /**
     * 定义验证规则
     * @var array
     */
    protected $rule = [
      'code'=>'require|isNotEmpty'
    ];
    
    /**
     * 定义错误信息
     * @var array
     */	
    protected $message = [
      'code'=>'code 是必须的，不可以为空'
    ];
}
