<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/8/23 11:30
 * |  Mail: 756684177@qq.com
 * |  Desc: 描述信息
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\api\service;


use app\common\repositories\contracts\ChannelRepositoryInterface;

class PaymentServiceRepository extends BaseService
{
    /**
     * 渠道实例
     * @var ChannelRepositoryInterface|null
     */
    private $channelRepository= null;

    /**
     * 注入渠道
     * PaymentServiceRepository constructor.
     * @param ChannelRepositoryInterface $channelRepository
     */
    public function __construct(ChannelRepositoryInterface $channelRepository)
    {
        $this->channelRepository = $channelRepository;
    }

    /**
     * 网关
     * @param $params
     * @return array
     */
    public function gateWay($params)
    {
        $result =  $this->channelRepository->gateWay($params);
        if ($result){
            return $this->returnData(true, '订单创建成功！',0,  $result);
        }else{
            $error = $this->channelRepository->getError();
            return $this->returnData(false,$error['msg'], $error['errorCode'],  $error['data']);
        }
    }

    /**
     * 银联wap
     */
    public function unPayWap($params)
    {
        $result =  $this->channelRepository->gateWay($params);
        if ($result){
            return $this->returnData(true, '订单创建成功！',0,  $result);
        }else{
            $error = $this->channelRepository->getError();
            return $this->returnData(false,$error['msg'], $error['errorCode'],  $error['data']);
        }
    }
}