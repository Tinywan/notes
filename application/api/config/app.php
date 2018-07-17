<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/7/5 18:02
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 描述信息
 * '------------------------------------------------------------------------------------------------------------------*/

return [
    // 应用名称
    'app_name' => '开放接口',
    // 应用地址
    'app_host' => 'openapi.tinywan.com',
    // 应用调试模式
    'app_debug' => true,
    //渠道路由
    'channel_class' => [
        'alipay' => \app\common\library\repositories\channel\AliPay::class,
    ],
    'payment_method' => [
        'web' => 'web', //电脑网页
        'wxSm' => 'wxSm', //微信扫码
        'wxGzh' => 'wxGzh', //微信公众号
        'wxH5' => 'wxH5',  //微信h5
        'aliSm' => 'aliSm',  //支付宝扫码
        'aliH5' => 'aliH5', //支付宝h5
        'aliWap' => 'aliWap', //支付宝wap
        'gateWay' => 'gateWay', //银行网银网关
        'unPayWap' => 'unPayWap', //银联wap
        'unQuickpay' => 'unQuickpay', //银行快捷支付
        'qqSm' => 'qqSm', //qq钱包扫码
        'aliF2f' => 'aliF2f', //支付宝当面付
    ],
    // 默认输出类型
    'default_return_type' => 'json',
    // 异常处理handle类 留空使用 \think\exception\Handle
    'exception_handle'       => \app\common\library\exception\ExceptionHandler::class,
];