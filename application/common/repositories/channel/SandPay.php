<?php
/**
 * Created by PhpStorm.
 * User: tinyw
 * Date: 2018/8/22
 * Time: 11:22
 */

namespace app\common\repositories\channel;

use app\common\repositories\contracts\ChannelRepositoryAbstract;
use think\facade\Log;

class SandPay extends ChannelRepositoryAbstract
{
    public function setChannelId()
    {
        // TODO: Implement setChannelId() method.
    }

    public function gateWay($option)
    {
        Log::debug("杉得的通道");
        echo __FUNCTION__.":::".__CLASS__;
    }

    public function unQuickpay($option)
    {
        Log::debug("杉得的通道".__FUNCTION__.":::".__CLASS__);
        echo __FUNCTION__.":::".__CLASS__;
    }

    public function unPayWap($option)
    {
        Log::debug("杉得的通道".__FUNCTION__.":::".__CLASS__);
        echo __FUNCTION__.":::".__CLASS__;
    }
}