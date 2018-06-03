<?php
/** .-----------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |-------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/3/28 14:20
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 商品订单枚举
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\common\library\enum;


class OrderStatusEnum
{
    //1:未支付
    const UNPAID = 1;

    //2：已支付
    const PAID = 2;

    //3：已发货
    const DELIVERED = 3;

    //4: 已支付，但库存不足
    const PAID_BUT_OUT_OF = 4;
}