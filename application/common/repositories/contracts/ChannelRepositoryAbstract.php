<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/8/21 15:51
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 支付渠道抽象类
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\common\repositories\contracts;


abstract class ChannelRepositoryAbstract implements ChannelRepositoryInterface
{
    /**
     * @var string 异步通知
     */
    protected $notifyUrl = '';

    /**
     * @var string 同步通知
     */
    protected $returnUrl = '';

    /**
     * 渠道路由列表
     * @var
     */
    protected $channelClass;

    /**
     * @var array 错误
     */
    protected $error = [
        'success' => false,
        'msg' => '未知错误',
        'errorCode' => 0,
        'channel' => 0,
        'data' => []
    ];

    public function __construct()
    {
        $this->channelClass = config('channel_class');
    }

    /**
     * 设置错误信息
     * @param $success
     * @param $message
     * @param int $errorCode
     * @param array $data
     * @return array
     */
    public function setError($success, $message, $errorCode = 0, array $data = [])
    {
        $this->error = [
            'success' => $success,
            'msg' => $message,
            'errorCode' => $errorCode,
            'data' => $data
        ];
        return $success;
    }

    /**
     * 获取错误信息
     * @return array
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * 设置渠道ID
     * @return mixed|void
     */
    public function setChannelId()
    {
        // TODO: Implement setChannelId() method.
    }

    public function wxGzh($option)
    {
        // TODO: Implement wxGzh() method.
    }

    public function wxSm($option)
    {
        // TODO: Implement wxSm() method.
    }

    public function wxH5($option)
    {
        // TODO: Implement wxH5() method.
    }

    public function qqSm($option)
    {
        // TODO: Implement qqSm() method.
    }

    public function aliSm($option)
    {
        // TODO: Implement aliSm() method.
    }

    public function aliH5($option)
    {
        // TODO: Implement aliH5() method.
    }

    public function aliWap($option)
    {
        // TODO: Implement aliWap() method.
    }

    public function gateWay($option)
    {
        // TODO: Implement gateWay() method.
    }

    public function unQuickpay($option)
    {
        // TODO: Implement unQuickpay() method.
    }

    public function unPayWap($option)
    {
        // TODO: Implement unPayWap() method.
    }

    public function cash($option)
    {
        // TODO: Implement cash() method.
    }

    public function cashQuery($option)
    {
        // TODO: Implement cashQuery() method.
    }

    public function notify($data)
    {
        // TODO: Implement notify() method.
    }
}