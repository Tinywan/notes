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
use think\Log;
use think\Request;

class ExceptionHandler extends Handle
{
    // http 状态码
    private $code;

    // 错误信息
    private $msg;

    // 错误代码
    private $errorCode;

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
            $this->msg = 'sorry，we make a mistake. (^o^)Y';
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

    /*
     * 异常写入日志
     */
    private function recordErrorLog(Exception $e)
    {
        Log::init([
            'type'  =>  'File',
            'path'  =>  LOG_PATH,
            'level' => ['error'],
            'apart_level' => ['error', 'sql']
        ]);
//        Log::record($e->getTraceAsString());
        Log::record($e->getMessage(),'error');
    }
}