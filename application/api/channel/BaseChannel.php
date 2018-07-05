<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/7/5 22:26
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 描述信息
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\api\channel;


class BaseChannel
{
    protected $notifyUrl = '';

    protected $returnUrl = '';

    public $returnMsg = [
      'success' => false,
      'msg' => '未知错误',
      'errorCode' => 0,
      'data' => []
    ];


    /**
     * 设置返回消息
     * @param $success
     * @param $message
     * @param int $code
     * @param array $data
     * @return mixed
     */
    protected function setReturnMsg($success, $message, $code = 0, array $data = [])
    {
        $this->returnMsg = [
          'success' => $success,
          'msg' => $message,
          'code' => $code,
          'data' => $data
        ];
        return $success;
    }

    /**
     * 获取返回消息
     * @return array
     */
    public function getReturnMsg()
    {
        return $this->returnMsg;
    }
}