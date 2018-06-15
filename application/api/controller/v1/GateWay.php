<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/6/12 10:19
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 选择业务订单跳转至支付网关
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\api\controller\v1;


use think\App;
use think\facade\Validate;

class GateWay
{
    /**
     * api列表
     */
    const apiList = [
        //网关支付
        'shop.payment.gateWay' => [\app\api\controller\v1\Pay::class, 'gateWay'],
        'shop.payment.unPayWap' => [\app\api\controller\v1\Pay::class, 'unPayWap'],
        'shop.payment.unQuickpay' => [\app\api\controller\v1\Pay::class, 'unQuickpay'], //银联快捷
        'shop.payment.qqSm' => [\app\api\controller\v1\Pay::class, 'qqSm'], //qq扫码
        'shop.payment.wxGzh' => [\app\api\controller\v1\Pay::class, 'wxGzh'], //微信公众号
        'shop.payment.wxSm' => [\app\api\controller\v1\Pay::class, 'wxSm'], //微信扫码
        'shop.payment.wxH5' => [\app\api\controller\v1\Pay::class, 'wxH5'], //微信H5支付
        'shop.payment.aliSm' => [\app\api\controller\v1\Pay::class, 'aliSm'], //支付宝扫码
        'shop.payment.aliH5' => [\app\api\controller\v1\Pay::class, 'aliH5'], //支付宝h5

        'shop.payment.agentPay' => [\app\api\controller\v1\Pay::class, 'agentPay'], //代付
        'shop.payment.agentPayQuery' => [\app\api\controller\v1\Pay::class, 'agentPayQuery'], //代付查询

        'shop.payment.transferPay' =>  [\app\api\controller\v1\Pay::class, 'transferPay'], //转账模式支付请求
    ];

    public function Payment()
    {
        // 1、接收参数
        $post = request()->post();
        $vaildate = new Validate([
            'mch_id'    => 'require',
            'method'    => 'require',
            'version'   => 'require',
            'timestamp' => 'require',
            'content'   => 'require',
            'sign'      => 'require',
        ], [], [
            'mch_id'    => '商户ID',
            'method'    => 'api名称',
            'version'   => '版本号',
            'timestamp' => '时间因子',
            'content'   => '请求参数',
            'sign'      => '签名',
        ]);

        // 2、接收验证
        if ($$vaildate->check($post)) {
            return ['error'];
        }

        // 3、商户信息验证

        // 4、发起支付请求，具体的哪一个渠道商的那个支付方式
        $container = \think\facade\App::container();
        $app = $container->invokeClass(self::apiList[$post['method'][0]]);
        // 5、结束支付
    }
}