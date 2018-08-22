<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/8/21 15:51
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 具体的对接类
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\common\repositories\channel;


use app\common\repositories\contracts\ChannelRepositoryAbstract;
use think\facade\App;
use think\facade\Log;

class ChannelRepository extends ChannelRepositoryAbstract
{
    /**
     * 仓库管理员对接
     * @param $option
     * @return array|bool
     */
    public function pay($option)
    {
        Log::debug('[渠道仓库管理员参数] '.json_encode($option));
        // 实例化渠道类
        $method = $option['method'];
        $channel = strtolower($option['channel']);
        $app = App::invokeClass($this->channelClass[$channel]);

        // 通道支付请求
        $result = $app->$method($option);
        Log::debug('[渠道返回信息]'.json_encode($result));
        if (!$result){
            $error = $app->getError();
            Log::error('[渠道错误]'.json_encode($error));
            return $this->setError(false, $error['msg'], $error['errorCode'], $error['data']);
        }
        return false;
    }
}