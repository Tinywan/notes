<?php
/**.-------------------------------------------------------------------------------------------------------------------
 * |  Github: https://github.com/Tinywan
 * |  Blog: http://www.cnblogs.com/Tinywan
 * |--------------------------------------------------------------------------------------------------------------------
 * |  Author: Tinywan(ShaoBo Wan)
 * |  DateTime: 2018/7/20 13:13
 * |  Mail: Overcome.wan@Gmail.com
 * |  Desc: 描述信息
 * '------------------------------------------------------------------------------------------------------------------*/

namespace app\common\services;


class OrderService
{
    /**
     * 計算折扣
     * @param int $qty
     * @return float
     */
    public function getDiscount($qty)
    {
        if ($qty == 1) {
            return 1.0;
        } elseif ($qty == 2) {
            return 0.9;
        } elseif ($qty == 3) {
            return 0.8;
        } else {
            return 0.7;
        }
    }

    /**
     * 计算最后的卖钱
     * @param integer $qty
     * @param float $discount
     * @return float
     */
    public function getTotal($qty, $discount)
    {
        return 500 * $qty * $discount;
    }
}