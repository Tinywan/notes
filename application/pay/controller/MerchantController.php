<?php
/** .-----------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |-------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/6/2 13:41
 * |  Mail: Overcome.wan@Gmail.com
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\pay\controller;

use app\common\controller\PayController;
use app\common\model\Merchant;

class MerchantController extends PayController
{
    public function index()
    {
        $mchId = get_next_id('merchant');
        $salt = get_salt();
        $res = Merchant::create([
          'id' => $mchId,
          'merchant_name' => '长春恒亨台'.rand(1111,9999),
          'password' => md5(md5('123456').md5($salt)),
          'salt' => $salt,
          'key' => '0d8cee92eed880b379fde0b78cbdc173',
        ]);
        halt($res);
    }
}
