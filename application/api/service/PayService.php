<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/6/21 21:43
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 支付信息处理
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\api\service;

use think\Exception;
use think\facade\Log;
use think\facade\Request;
use Yansongda\Pay\Pay;

class PayService extends AbstractService
{
    //异步回调
    public function notifyUrl()
    {
        // 公共的数据处理
        $postStr = Request::getInput();
        $tmpArr = explode('&', $postStr);
        $postData = [];
        foreach ($tmpArr as $value) {
            $tmp = explode('=', $value);
            $postData[$tmp[0]] = $tmp[1];
        }
        Log::error(get_current_date() . '支付异步消息: ' . json_encode($postData));

        // 支付渠道路由
        $channelName = '';
        if ($postData['trade_status']) {
            $channelName = 'alipay';
        } elseif ($postData['trade_wechat']) {
            $channelName = 'alipay';
        }
        // 参数验证
        $alipay = Pay::alipay(config('pay.alipay'));
        try {
            // 第四步 使用RSA的验签方法，通过签名字符串、签名参数（经过base64解码）及支付宝公钥验证签名
            $res = $alipay->verify($postData);
            Log::debug('Alipay notify', $res->all());
        } catch (Exception $e) {
            return json([
                'code' => 500,
                'msg' => $e->getMessage()
            ]);
        }
        // 加入队列
        $redis = location_redis();
        $millisecond = get_millisecond();
        $res = $redis->zAdd(self::ORDER_DELAY_KEY, $millisecond, json_encode($postData));
        if ($res) {
            return $alipay->success()->send();
        }
    }

}