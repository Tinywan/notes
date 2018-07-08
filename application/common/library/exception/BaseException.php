<?php
/** .-----------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |-------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/3/20 10:47
 * |  Mail: Overcome.wan@Gmail.com
 * |  Fun:  异常类基类
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\common\library\exception;


use think\Exception;

/**
 * Class BaseException
 * 自定义异常类的基类
 */
class BaseException extends Exception
{
    // http 状态码
    public $code = 400;

    // 错误信息
    public $msg = 'invalid parameters';

    // 错误代码
    public $errorCode = 999;

    public $shouldToClient = true;

    public function __construct($params = [])
    {
        if (!is_array($params)) {
            return ;
            //throw new Exception("参数必须是数组");
        }

        // 判断是否有着数组键存在
        if (array_key_exists('code', $params)) {
            $this->code = $params['code'];
        }

        if (array_key_exists('msg', $params)) {
            $this->msg = $params['msg'];
        }

        if (array_key_exists('errorCode', $params)) {
            $this->errorCode = $params['errorCode'];
        }
    }
}