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

use app\common\library\enum\OrderStatusEnum;
use app\common\model\Order;
use think\Db;
use think\Exception;
use think\facade\Log;
use think\facade\Request;
use Yansongda\Pay\Pay;
use app\common\model\WxOrder as WxOrderModel;

class PayController
{
    // 同步结果 Get 方式
    public function returnUrl(Request $request)
    {
        $getData = $request->param();
        $aliPay = Pay::alipay(config('pay.alipay'));
        try {
            $data = $aliPay->verify($getData);
            Log::error(getCurrentDate() . '-支付宝同步结果-' . json_encode($data->all()));
        } catch (Exception $e) {
            Log::error(getCurrentDate() . '-支付宝同步异常信息-' . json_encode($e->getMessage()));
        }
        // 签名验证无异常
        if (!empty($data)) {
            echo '同步结果 success';
            halt($data);
        }
    }

    // 异步通知 POST
    public function notifyUrl(Request $request)
    {
        // 第一步： 在通知返回参数列表中，除去sign、sign_type两个参数外，凡是通知返回回来的参数皆是待验签的参数。
        // 第二步： 将剩下参数进行url_decode, 然后进行字典排序，组成字符串，得到待签名字符串：
        // 第三步： 将签名参数（sign）使用base64解码为字节码串。
        if ($request->isPost()) {
            $postData = $request->param();
            Log::error(get_current_date() . '-支付宝异步通知数据-' . json_encode($postData));
            $alipay = Pay::alipay(config('pay.alipay'));
            try {
                // 第四步 使用RSA的验签方法，通过签名字符串、签名参数（经过base64解码）及支付宝公钥验证签名
                $data = $alipay->verify($postData);
                //Log::error(getCurrentDate().'-支付宝异步通知结果-'.json_encode($data->all()));
            } catch (Exception $e) {
                return json([
                  'code' => 500,
                  'msg' => $e->getMessage()
                ]);
            }
            // 第五步：需要严格按照如下描述校验通知数据的正确性。
            // 1、商户需要验证该通知数据中的out_trade_no是否为商户系统中创建的订单号
            $orderInfo = WxOrderModel::get(['order_no' => $data['out_trade_no']]);
            if (empty($orderInfo)) {
                Log::error(get_current_date() . '订单不存在');
            }

            // 2、判断total_amount是否确实为该订单的实际金额（即商户订单创建时的金额）
            if ($orderInfo['total_price'] != $data['total_amount']) {
                Log::error(get_current_date() . '订单金额与发起支付金额不一致=》' . json_encode($data));
            }

//            // 3、校验通知中的 (卖家支付宝用户号) seller_id（或者seller_email) 是否为out_trade_no这笔单据的对应的操作方（有的时候，一个商户可能有多个seller_id/seller_email）
//            if($orderInfo['seller_id'] != $data['seller_id']){
//                Log::error(getCurrentDate().'订单金额与发起支付金额不一致=》'.json_encode($data));
//            }
//
//            // 4、验证app_id是否为该商户本身 http://http.tinywan.com/frontend/pay/test001
//            // 4、验证app_id是否为该商户本身 http://http.tinywan.com/frontend/pay/index
//            if($orderInfo['app_id'] != $data['app_id']){
//                Log::error(getCurrentDate().'订单金额与发起支付金额不一致=》'.json_encode($data));
//            }
            Log::error(get_current_date() . "订单状态更新开始");
            // 交易状态 trade_status 成功
            if ($data['trade_status'] == "TRADE_SUCCESS") {
                // 启动事务
                Db::startTrans();
                try{
                    if ($orderInfo['status'] == 1) {
                        $orderUpdate['id'] = $orderInfo->id;
                        $orderUpdate['status'] = OrderStatusEnum::PAID;
                        $orderUpdate['pay_time'] = time(); // 正对当前服务器
                        $orderUpdate['trade_no'] = $data['trade_no'];
                        $orderUpdate['notify_time'] = strtotime($data['notify_time']);
                    }
                    //更新订单状态
                    Db::name('wx_order')->update($orderUpdate);
                    Db::commit();
                }catch (Exception $e){
                    Db::rollback();
                    Log::error("订单状态更新异常" . $e->getMessage());
                    throw $e;
                }
                Log::error(get_current_date() . "订单状态更新结束");
            }
            // 信息通知
            // static::sendMsg($orderInfo);
            return $alipay->success()->send();
        } else {
            return json([
              'code' => 403,
              'msg' => 'this is not a valid post request'
            ]);
        }
    }
}