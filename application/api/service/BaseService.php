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


class BaseService
{
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