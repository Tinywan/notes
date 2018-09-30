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


use app\common\repositories\abstracts\ChannelRepositoryInterface;
use think\facade\Validate;

abstract class ChannelRepositoryAbstract extends RepositoryAbstract implements ChannelRepositoryInterface
{
    /**
     * 异步通知URL
     * @var string
     */
    protected $notifyUrl = '';

    /**
     * 同步通知URL
     * @var string
     */
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
     * @var array 商户渠道用户配置
     */
    public $mchChannelAccountConfig = [];

    /**
     * @var array 返回信息
     */
    protected $returnMsg = [
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
        $this->setChannel();
    }

    /**
     * 设置商户渠道配置
     * @param $mch_id 商户id
     * @param string $channel_mch_id 渠道商户号
     * @return mixed|null|static
     * @throws \think\exception\DbException
     */
    public function setMchChannelConfig($mch_id, $channel_mch_id = '')
    {
        $result = MerchantChannelConfig::get(['mch_id' => $mch_id, 'channel' => $this->payConfig->class_namespace]);
        if (empty($result)) {
            return $this->setError(false, '账号未配置商户信息');
        }
        if ($result->status == 0) {
            return $this->setError(false, '账号配置商户号已被禁用');
        }

        if (empty($channel_mch_id)) {
            $channel_merchant = ChannelMerchant::get($result->channel_merchant_id);
        } else {
            $channel_merchant = ChannelMerchant::get(['channel_mch_id' => $channel_mch_id, 'channel' => $this->payConfig->class_namespace]);
        }

        if (empty($channel_merchant)) {
            return $this->setError(false, '渠道商户号不存在');
        }
        if ($channel_merchant->status == 0) {
            return $this->setError(false, '渠道商户号已被停用');
        }

        return $this->mchChannelConfig = $channel_merchant;
    }

    /**
     * 直接设置渠道商户号配置
     * @param $channel_merchant_id 记录ID
     * @return ChannelMerchant|null
     * @throws \think\exception\DbException
     */
    public function setChannelConfig($channel_merchant_id)
    {
        $channel_merchant = ChannelMerchant::get(['channel_mch_id' => $channel_merchant_id, 'channel' => $this->payConfig->class_namespace]);
        return $this->mchChannelConfig = $channel_merchant;
    }

    /**
     * 重设异步通知地址
     * @param $notify_url
     */
    public function setNotifyUrl($notify_url)
    {
        $this->notifyUrl = $notify_url;
    }

    /**
     * 重设同步通知地址
     * @param $return_url
     */
    public function setReturnUrl($return_url)
    {
        $this->returnUrl = $return_url;
    }

    /**
     * 设置平台配置
     * @throws \think\exception\DbException
     */
    public function setChannel()
    {
        $this->payConfig = PayChannelConfig::get($this->setChannelId());
    }

    /**
     * 设置渠道id
     * @return mixed
     */
    abstract public function setChannelId();

    /**
     * 微信公众号
     * @return mixed
     */
    abstract public function wxGzh($option);

    /**
     * 微信扫码
     * @return mixed
     */
    abstract public function wxSm($option);

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
     * 支付宝h5
     * @return mixed
     */
    abstract public function aliH5($option);

    /**
     * 支付宝wap
     * @return mixed
     */
    abstract public function aliWap($option);

    /**
     * 银行网银网关
     * @return mixed
     */
    abstract public function gateWay($option);

    /**
     * 银行快捷支付
     * @param $option
     * @return mixed
     */
    abstract public function unQuickpay($option);

    /**
     * 银联wap
     * @param $option
     * @return mixed
     */
    abstract public function unPayWap($option);

    /**
     * qq扫码
     * @param $option
     * @return mixed
     */
    abstract public function qqSm($option);

    /**
     * 异步通知
     * @return mixed
     */
    abstract public function notify($data);

    /**
     * 账户金额查询
     * @param $option
     * @return mixed
     */
    abstract function balance($option);

    /**
     * 异步通知成功处理响应信息
     * @return mixed
     */
    abstract function notifySuccessResponse();

    /**
     * 提现
     * @param $option
     * @return mixed
     */
    abstract function cash($option);

    /**
     * 提现查询
     * @param $option
     * @param string $channel_order_no
     * @return mixed
     */
    abstract function cashQuery($option, $channel_order_no = '');

    /**
     * 获取最终返回结果
     */
    public function getReturnMsg()
    {
        return $this->returnMsg;
    }

    /**
     * 验证
     * @param $data
     * @param $rule
     * @param $field
     * @return bool|mixed
     */
    protected function vaildate($data, $rule, $field)
    {
        $validate = Validate::make($rule, [], $field);
        if (!$validate->check($data)) {
            return $this->setError(false, $validate->getError());
        }
        return true;
    }
}