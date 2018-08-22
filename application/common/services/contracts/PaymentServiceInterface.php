<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/8/21 15:51
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 支付方式接口
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\common\services\contracts;


interface PaymentServiceInterface
{
    /**
     * 网关支付方式
     * @param $params
     * @return mixed
     */
    public function gateWay($params);

    /**
     * 银联wap
     * @param $params
     * @return mixed
     */
    public function unPayWap($params);

    /**
     * 银联快捷
     * @param $params
     * @return mixed
     */
    public function unQuickPay($params);

    /**
     * QQ 扫码
     * @param $params
     * @return mixed
     */
    public function qqScanCode($params);

    /**
     * 微信公众号
     * @param $params
     * @return mixed
     */
    public function wxGzh($params);

    /**
     * 微信扫码
     * @param $params
     * @return mixed
     */
    public function wxScanCode($params);

    /**
     * 渠道代付
     * @param $params
     * @return mixed
     */
    public function agentPay($params);

    /**
     * 转账
     * @param $params
     * @return mixed
     */
    public function transferPay($params);
}