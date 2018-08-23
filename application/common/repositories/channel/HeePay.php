<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/8/21 15:51
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 汇付宝通道
 * '------------------------------------------------------------------------------------------------------------------*/
namespace app\common\repositories\channel;


use app\common\repositories\contracts\ChannelRepositoryAbstract;
use think\facade\Log;

class HeePay extends ChannelRepositoryAbstract
{
    public function setChannelId()
    {
        // TODO: Implement setChannelId() method.
    }

    public function gateWay($option)
    {
        Log::debug("[汇元银通]接受参数".json_encode($option));
        if(!$option['mch_id']){
            return $this->setError(false,'商户参数错误');
        }
    }

    public function unQuickpay($option)
    {
        Log::debug("杉得的通道".__FUNCTION__.":::".__CLASS__);

    }

    public function unPayWap($option)
    {
        Log::debug("杉得的通道".__FUNCTION__.":::".__CLASS__);
    }
}