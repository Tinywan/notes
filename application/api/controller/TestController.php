<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/7/9 13:41
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 描述信息
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\api\controller;

use app\common\library\exception\UserException;

class TestController
{
    /**
     * 测试自定义异常类
     * @param int $id
     * @throws UserException
     */
    public function lastError($id = 1)
    {
        if ($id = 1) {
            throw new UserException();
        }
    }
}