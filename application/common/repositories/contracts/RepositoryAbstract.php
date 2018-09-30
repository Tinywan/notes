<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/8/24 13:39
 * |  Mail: 756684177@qq.com
 * |  Desc: 描述信息
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\common\repositories\contracts;


abstract class RepositoryAbstract implements RepositoryInterface
{
    /**
     * @var array 错误
     */
    public $error = [
        'success' => false,
        'message' => '未知错误1',
        'code'    => 0,
        'data' => []
    ];

    /**
     * @param $success
     * @param $message
     * @param int $code
     * @param array $data
     * @return mixed
     */
    public function setError($success, $message, $code = 0, $data = [])
    {
        $this->error = [
            'success' => $success,
            'message' => $message,
            'code'    => $code,
            'data'    => $data
        ];
        return $success;
    }

    /**
     * 获取错误信息
     * @return array
     */
    public function getError()
    {
        return $this->error;
    }
}