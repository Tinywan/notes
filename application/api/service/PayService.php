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


use app\common\library\repositories\eloquent\PayRepository;

class PayService extends BaseService
{
    // 支付仓库实例
    protected $payRepository;

    /**
     * PayService constructor.
     * @param PayRepository $payRepository
     */
    public function __construct(PayRepository $payRepository)
    {
        $this->payRepository = $payRepository;
    }

    /**
     * web支付
     */
    public function web($params)
    {
        $result = $this->payRepository->pay(__FUNCTION__, $params);
        if ($result) {
            return $this->returnData(true, '订单创建成功！', 0, $result);
        } else {
            $error = $this->payRepository->getError();
            return $this->returnData(false, $error['errorCode'], $error['msg'], $error['data']);
        }
    }

}