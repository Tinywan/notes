<?php
/** .-----------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |-------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/3/20 10:47
 * |  Mail: Overcome.wan@Gmail.com
 * |  Fun:  TP5错误异常类处理
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\common\library\exception;

use Exception;
use think\exception\Handle;
use think\facade\Env;
use think\facade\Log;
use think\facade\Request;

class ExceptionHandler extends Handle
{
    // http 状态码 200、404
    private $code;

    // 错误具体信息
    private $msg;

    // 自定义的错误代码
    private $errorCode;

    /**
     * 重写reader方法
     * @auther Tinywan 756684177@qq.com
     * @DateTime 2018/8/23 22:10
     * @param Exception $e
     * @return \think\Response|\think\response\Json
     */
    public function render(Exception $e)
    {
        if ($e instanceof BaseException)
        {
            //自定义不需要记录日志
            $this->code = $e->code;
            $this->msg = $e->msg;
            $this->errorCode = $e->errorCode;
        }
        else{
            // 如果是服务器未处理的异常，将http状态码设置为500，并记录日志
            if(config('app_debug')){
                // 调试状态下需要显示TP默认的异常页面，因为TP的默认页面
                // 直接使用框架自己的日志记录格式
                return parent::render($e);
            }

            $this->code = 404;
            $this->msg = 'sorry，we make a mistake. ( ^_^ )';
            $this->errorCode = 999;
            $this->recordErrorLog($e);
        }

        $request = Request::instance();
        $result = [
            'msg'  => $this->msg,
            'error_code' => $this->errorCode,
            'request_url' => $request = $request->url()
        ];
        return json($result, $this->code);
    }

    /**
     * 异常写入日志
     * @auther Tinywan 756684177@qq.com
     * @DateTime 2018/8/23 22:10
     * @param Exception $e
     */
    private function recordErrorLog(Exception $e)
    {
        Log::init([
            'type'  =>  'File',
            'path'  =>  Env::get('ROOT_PATH') . '/logs',
            'level' => ['error'],
            'apart_level' => ['error', 'sql']
        ]);
        Log::record($e->getMessage().'|'.$e->getTraceAsString(),'error');
    }
}