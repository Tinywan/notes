<?php

/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/7/8 10:47
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 描述信息
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\common\library\repositories\eloquent;

use app\common\library\repositories\contracts\RepositoryInterface;
use app\common\library\repositories\exceptions\RepositoryException;
use think\Exception;
use think\facade\App;
use think\Model;

abstract class ChannelAbstractRepository implements RepositoryInterface
{
    protected $notifyUrl = '';

    protected $returnUrl = '';

    /**
     * @var array 渠道配置
     */
    public $payConfig = [];

    /**
     * @var array 商户渠道配置
     */
    public $mchChannelConfig = [];

    /**
     * @var array 商户渠道用户配置（针对渠道必须创建用户的情况，环讯）
     */
    public $mchChannelAccountConfig = [];

    /**
     * @var array 返回信息
     */
    protected $error = [
      'success' => false,
      'code' => 0,
      'message' => '未知错误',
      'channel' => 0, //支付通道
      'data' => []
    ];

    public function __construct()
    {
        $this->notifyUrl = config('server_url') . '/notify';
        $this->returnUrl = config('server_url') . '/return';
    }

    /**
     * 设置返回信息
     * @param $success
     * @param $msg
     * @param int $errorCode
     * @return mixed
     */
    protected function setError($success, $msg, $errorCode = 0, array $data = [])
    {
        $this->error = [
          'success' => $success,
          'msg' => $msg,
          'errorCode' => $errorCode,
          'data' => $data,
        ];

        return $success;
    }

    abstract public function notifySuccess();

    /**
     * 获取最终返回结果
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * 微信H5
     * @return mixed
     */
    abstract public function wxH5($option);

    /**
     * 支付宝扫码
     * @return mixed
     */
    abstract public function aliSm($option);

    /**
     * 异步通知
     * @return mixed
     */
    abstract public function notifyUrl($data);
}