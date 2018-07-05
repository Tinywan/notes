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

use think\facade\App;
use think\facade\Log;
use think\facade\Request;
use Yansongda\Pay\Pay;

class PayService extends AbstractService
{
    /**
     * 同步回调
     * @return string
     */
    public function returnUrl()
    {
        $getData = Request::param();
        Log::debug(get_current_date() . ' [1] 支付同步结果 ' . json_encode($getData));
        // 1、支付渠道判断
        return true;
    }

    /**
     * 异步回调
     * @return $this|\think\response\Json
     */
    public function notifyUrl()
    {
        // 1、公共的数据处理
        $postStr = Request::getInput();
        Log::debug(get_current_date() . ' [1] 支付异步消息 ' . json_encode($postStr));
        $tmpArr = explode('&', $postStr);
        $postData = [];
        foreach ($tmpArr as $value) {
            $tmp = explode('=', $value);
            $postData[$tmp[0]] = $tmp[1];
        }

        // 2、支付渠道路由
        $channelName = 'alipay';
        if ($postData['trade_status']) {
            $channelName = 'alipay';
        } elseif ($postData['trade_wechat']) {
            $channelName = 'wechat';
        }

        Log::debug(get_current_date() . ' [2] 支付渠道 ' . $channelName);

        // 3、实例化渠道类
        $channelObj = App::invokeClass(config('payment_channel_route')[$channelName]);

        // 4、渠道类通知
        $result = $channelObj->notify($postData);
        if (!$result) {
            $channelError = $channelObj->getReturnMsg();
            return $this->setError(false, $channelError['msg'], $channelError['code']);
        }

        // 5、渠道回调
        if ($channelName == 'alipay') {
            $alipay = Pay::alipay(config('pay.alipay'));
            return $alipay->success()->send();
        } else {
            return false;
        }
    }

}