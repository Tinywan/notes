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

    // 接口服务列表
    'api_service_class' => [
        'pay.trade.web' =>        [\app\common\services\payment\PaymentService::class, 'web'],
        'pay.trade.gateWay' =>    [\app\common\services\payment\PaymentService::class, 'gateWay'],
        'pay.trade.unPayWap' =>   [\app\common\services\payment\PaymentService::class, 'unPayWap'],
        'pay.trade.unQuickpay' => [\app\common\services\payment\PaymentService::class, 'unQuickpay'],
        'agents.trade.pay' =>     [\app\api\service\AgentService::class, 'pay'],
        'agents.trade.cash' =>    [\app\api\service\AgentService::class, 'cash'],
    ],

    // 渠道路由列表
    'channel_class' => [
        'heepay' => \app\common\repositories\channel\HeePay::class,
        'sandpay' => \app\common\repositories\channel\SandPay::class,
    ],

    // 网关接口列表
    'api_method_list' => [
        'pay.trade.gateWay' => 'gateWay',
        'pay.trade.unPayWap' => 'unPayWap',
        'pay.trade.unQuickpay' => 'unQuickpay'
    ],

    // 网关支付方式
    'payment_method' => [
        'web', //电脑网页
        'wxSm', //微信扫码
        'wxGzh', //微信公众号
        'wxH5',  //微信h5
        'aliSm',  //支付宝扫码
        'aliH5', //支付宝h5
        'aliWap', //支付宝wap
       // 'gateWay', //银行网银网关
        'unPayWap', //银联wap
        'unQuickpay', //银行快捷支付
        'qqSm', //qq钱包扫码
        'aliF2f', //支付宝当面付
    ],
    // 默认输出类型
    'default_return_type' => 'json',
    // 异常处理handle类 留空使用 \think\exception\Handle
    'exception_handle' => \app\common\library\exception\ExceptionHandler::class,
];