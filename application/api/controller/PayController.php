<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/6/21 21:44
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 描述信息
 * '------------------------------------------------------------------------------------------------------------------*/
namespace app\api\controller;

use think\facade\Log;
use think\facade\Request;

class PayController
{
    // 同步结果 Get 方式
    public function returnUrl()
    {
        // 接口 Get 方式返回信息
        //        array (size=12)
        //      'charset' => string 'GBK' (length=3)
        //      'out_trade_no' => string '530551529590119' (length=15)
        //      'method' => string 'alipay.trade.page.pay.return' (length=28)
        //      'total_amount' => string '0.01' (length=4)
        //      'sign' => string 'H1DvUt5LSjWTh8BoqORR7Tuo/ljzloXNR1GHfPmestKXhnCswZpZNlv0QQwxNKGSJ0ZWh4jtBLQUNiLosbUu8JtaJgyCmVhyTpIR0ut1UzoWRZrlrt1QpOAVVBr+rRRxqTLvWXRAiLausRqq/a41QOWupDDlL/qx+vs65+wZ5qTKm8OXIg7b8K0w73gupPb4z+icny8QPvPe1HSCPnB+BJ+293SxCuooP0cFFKRl7CZUlTMK+u+To72KjFkz4sWtCkyYgvr8YeqlnAQ6z+f0/pEiKEQC6Pd8jSz3zsico0KqODkkeeAAxUtJQb2AW/KlRnQjVv8ypVOyGC3PP+shjQ==' (length=344)
        //      'trade_no' => string '2018062121001004330200542826' (length=28)
        //      'auth_app_id' => string '2016090900470841' (length=16)
        //      'version' => string '1.0' (length=3)
        //      'app_id' => string '2016090900470841' (length=16)
        //      'sign_type' => string 'RSA2' (length=4)
        //      'seller_id' => string '2088102174818255' (length=16)
        //      'timestamp' => string '2018-06-21 22:09:03' (length=19)
        echo 111111111;
        $redis = message_redis();
        $redis->set('ALI_PAY_RETURN_URL',json_encode($_GET));
        halt($_GET);
    }

    // 异步通知 POST
    public function notifyUrl()
    {
        Log::error('----------notifyUrl----1------'.json_encode($_POST));
        Log::error('----------notifyUrl----2------'.json_encode(Request::post()));
        $redis = message_redis();
        $redis->set('ALI_PAY_NOTIFY_URL',json_encode($_POST));
        return "Success";
    }
}