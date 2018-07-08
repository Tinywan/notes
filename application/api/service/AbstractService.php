<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/7/5 17:42
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 描述信息
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\api\service;


abstract class AbstractService
{
    /**
     * @var array 错误信息
     */
    public $error = [
      'success' => false,
      'msg' => '未知错误',
      'errorCode' => 0,
      'data' => []
    ];

    /**
     * 设置错误信息
     * @param $success
     * @param $msg
     * @param int $errorCode
     * @param $data
     * @return mixed
     */
    public function setError($success, $msg, $errorCode = 0, array $data = [])
    {
        $this->error = [
          'success' => $success,
          'msg' => $msg,
          'errorCode' => $errorCode,
          'data' => $data
        ];
        return $success;
    }

    /**
     * 获取错误
     * @return array
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * 返回数据
     * @param $success
     * @param string $msg
     * @param int $errorCode
     * @param array $data
     * @return array
     */
    protected function returnData($success, $msg = '', $errorCode = 0, array $data = [])
    {
        return [
          'success' => $success,
          'msg' => $msg,
          'errorCode' => $errorCode,
          'data' => $data
        ];
    }
}