<?php

/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/6/3 21:11
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 描述信息
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\api\controller\v1;

use app\common\controller\BaseApiController;

class TokenController extends BaseApiController
{
  /**
   * 获取接口令牌
   * origin:  http://tp51.env/api/v1.token/getToken
   * route: http://tp51.env/api/v1/token/user
   * @param string $code
   * @return string
   */
    public function getToken($code = '123456')
    {
       return $code;
    }
}