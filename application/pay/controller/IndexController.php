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

use app\api\channel\AliPay;
use app\api\facade\WeChat;
use app\api\service\PayService;
use app\common\controller\PayController;
use think\Db;
use think\facade\App;
use think\facade\Log;
use Yansongda\Pay\Pay;

class IndexController extends PayController
{
    public function index()
    {
        $order = [
            'out_trade_no' => rand(11111, 99999) . time(),
            'total_amount' => rand(11, 99),
            'subject' => '商品测试001' . rand(11111111, 888888888888),
        ];
        Log::error('-----------' . json_encode($order));
        $alipay = Pay::alipay(config('pay.alipay'))->web($order);
        return $alipay->send();
    }

    /**
     * 订单查询
     * {
     * "code": "10000",
     * "msg": "Success",
     * "buyer_logon_id": "cus***@sandbox.com",
     * "buyer_pay_amount": "0.00",
     * "buyer_user_id": "2088102169214338",
     * "buyer_user_type": "PRIVATE",
     * "invoice_amount": "0.00",
     * "out_trade_no": "242381531814325",
     * "point_amount": "0.00",
     * "receipt_amount": "0.00",
     * "send_pay_date": "2018-07-17 15:59:20",
     * "total_amount": "75.00",
     * "trade_no": "2018071721001004330200593832",
     * "trade_status": "TRADE_SUCCESS"
     * }
     */
    public function orderQuery()
    {
        $order = [
            'out_trade_no' => '242381531814325',
            'bill_type' => 'trade'
        ];
        $pay = \Yansongda\Pay\Pay::alipay(config('pay.alipay'));
        $result = $pay->find($order);
        Log::error('订单查询' . \GuzzleHttp\json_encode($result));

        switch ($result['code']) {
            case 10000:
                $resData['status'] = 2;
                $resData['msg'] = '接口调用成功';
                break;
            case 20000:
                $resData['status'] = -1;
                break;
            case 20001:
                $resData['status'] = -1;
                break;
            case 40001:
                $resData['status'] = 1;
                break;
            case 40002:
                $resData['status'] = -1;
                break;
            case 40004:
                $resData['status'] = -1;
                break;
            case 40006:
                $resData['status'] = -1;
                break;
            default:
                $resData['status'] = -1;
                $resData['msg'] = '未知错误信息';
        }

        $orderInfo = Db::name('order')->where(['order_no' => $result['out_trade_no']])->lock(true)->find();
        if (empty($orderInfo)) {
            return '订单未找到';
        }

        // 2、支付金额验证
        if ($orderInfo['total_fee'] != $result['total_amount']) {
            return '订单金额与发起支付金额不一致111';
        }

        // 3、未支付
        if ($orderInfo['status'] == 0) {
            // 4、根据支付渠道结果更新订单
            $orderUpdate = [];
            if ($result['trade_status'] == 'TRADE_SUCCESS') {
                $orderUpdate['status'] = 1;
                $orderUpdate['pay_time'] = time();
            } elseif ($result['trade_status'] == 'WAIT_BUYER_PAY') {
                $orderUpdate['status'] = -1;
                $orderUpdate['pay_time'] = time();
            } elseif ($result['trade_status'] == 'TRADE_FINISHED') {
                $orderUpdate['status'] = -1;
                $orderUpdate['pay_time'] = time();
            }

            $orderUpdate['channel_order_no'] = $result['trade_no'];
            $orderUpdate['channel_return_data'] = json_encode($result);

            // 5、修改用户账户
            try {
                Db::startTrans();
                // 6、更新订单状态
                Db::name('order')->where(['id' => $orderInfo['id']])->update($orderUpdate);
                // 7、账户平衡表
                Db::name('order')->where(['id' => $orderInfo['id']])->update($orderUpdate);
                Db::commit();
            } catch (Exception $e) {
                Db::rollback();
                Log::error('系统异常=》' . $e->getMessage() . '|' . $e->getTraceAsString());
                return "数据库修改系统异常";
            }
            return '处理成功';
        }
        echo 1114444444;
    }

    public function test(App $app)
    {
        $object = $app->invokeClass(AliPay::class);
        halt($object->gateWay());
    }

    public function test1()
    {
        $object = WeChat::gateWay();
        halt($object);
    }

    public function test34()
    {
        $channelObj = App::invokeClass(PayService::class);
        halt($channelObj);
    }
}
